<?php

namespace SendKuy\Tools;

final class ObjectGenerator
{
    private const EXTRA = ['BatchRow', 'Deleted', 'WhatsappNumberCheck'];

    /** @var array<string, array<string, mixed>> */
    private array $schemas;

    /**
     * @param  array<string, mixed>  $spec
     */
    public function __construct(array $spec)
    {
        $this->schemas = $spec['components']['schemas'] ?? [];
        ksort($this->schemas);
    }

    public static function fromFile(string $path): self
    {
        return new self(json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, string>
     */
    public function files(): array
    {
        $properties = [];
        $sources = [];
        $presence = [];
        $map = [];

        foreach (array_keys($this->schemas) as $name) {
            $class = $this->classFor($name);

            if ($class === null) {
                continue;
            }

            $flat = $this->flatten($this->schemas[$name]);
            $const = $flat['properties']['object']['const'] ?? null;

            if (is_string($const)) {
                $map[$const] = $class;
            }

            $sources[$class] = ($sources[$class] ?? 0) + 1;

            foreach ($flat['properties'] as $property => $definition) {
                $types = $this->types($definition);

                if (! in_array($property, $flat['required'], true)) {
                    $types[] = 'null';
                }

                $properties[$class][$property] = [...($properties[$class][$property] ?? []), ...$types];
                $presence[$class][$property] = ($presence[$class][$property] ?? 0) + 1;
            }
        }

        foreach ($presence as $class => $counts) {
            foreach ($counts as $property => $count) {
                if ($count < $sources[$class]) {
                    $properties[$class][$property][] = 'null';
                }
            }
        }

        ksort($properties);
        ksort($map);

        $files = [];

        foreach ($properties as $class => $types) {
            $files[$class.'.php'] = $this->renderClass($class, $types);
        }

        $files['ObjectTypes.php'] = $this->renderMap($map);

        return $files;
    }

    public function write(string $directory): int
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        foreach (glob($directory.'/*.php') ?: [] as $stale) {
            unlink($stale);
        }

        $files = $this->files();

        foreach ($files as $name => $contents) {
            file_put_contents($directory.'/'.$name, $contents);
        }

        return count($files);
    }

    private function classFor(string $name): ?string
    {
        $const = $this->flatten($this->schemas[$name] ?? [])['properties']['object']['const'] ?? null;

        if (is_string($const)) {
            return str_replace(' ', '', ucwords(str_replace('_', ' ', $const)));
        }

        return in_array($name, self::EXTRA, true) ? $name : null;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{properties: array<string, array<string, mixed>>, required: list<string>}
     */
    private function flatten(array $schema): array
    {
        if (isset($schema['$ref'])) {
            return $this->flatten($this->schemas[$this->refName($schema['$ref'])] ?? []);
        }

        $properties = [];
        $required = [];

        foreach ($schema['allOf'] ?? [] as $part) {
            $flat = $this->flatten($part);
            $properties = array_merge($properties, $flat['properties']);
            $required = [...$required, ...$flat['required']];
        }

        return [
            'properties' => array_merge($properties, $schema['properties'] ?? []),
            'required' => array_values(array_unique([...$required, ...($schema['required'] ?? [])])),
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function types(array $schema): array
    {
        if (isset($schema['$ref'])) {
            $name = $this->refName($schema['$ref']);
            $class = $this->classFor($name);

            return $class !== null ? [$class] : $this->types($this->schemas[$name] ?? []);
        }

        foreach (['oneOf', 'anyOf'] as $keyword) {
            if (isset($schema[$keyword])) {
                return array_merge(...array_map($this->types(...), $schema[$keyword]));
            }
        }

        if (isset($schema['allOf'])) {
            return ['SendKuyObject'];
        }

        if (array_key_exists('const', $schema)) {
            return [$this->scalar($schema['const'])];
        }

        $declared = $schema['type'] ?? null;
        $declared = is_array($declared) ? $declared : ($declared === null ? [] : [$declared]);

        if ($declared === []) {
            if (isset($schema['enum'])) {
                return array_values(array_unique(array_map($this->scalar(...), $schema['enum'])));
            }

            return isset($schema['properties']) ? ['SendKuyObject'] : ['mixed'];
        }

        $types = [];

        foreach ($declared as $type) {
            $types = [...$types, ...match ($type) {
                'string' => ['string'],
                'integer' => ['int'],
                'number' => ['int', 'float'],
                'boolean' => ['bool'],
                'null' => ['null'],
                'array' => ['list<'.$this->union($this->types($schema['items'] ?? [])).'>'],
                'object' => ['SendKuyObject'],
                default => ['mixed'],
            }];
        }

        return $types;
    }

    private function scalar(mixed $value): string
    {
        return match (true) {
            is_string($value) => 'string',
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_float($value) => 'float',
            $value === null => 'null',
            default => 'mixed',
        };
    }

    /**
     * @param  list<string>  $types
     */
    private function union(array $types): string
    {
        $types = array_values(array_unique($types));

        if (in_array('mixed', $types, true)) {
            return 'mixed';
        }

        $nullable = in_array('null', $types, true);
        $types = array_values(array_diff($types, ['null']));
        sort($types);

        if ($types === []) {
            return 'null';
        }

        return implode('|', $types).($nullable ? '|null' : '');
    }

    /**
     * @param  array<string, list<string>>  $properties
     */
    private function renderClass(string $class, array $properties): string
    {
        $lines = ['<?php', '', 'namespace SendKuy\\Objects;', '', 'use SendKuy\\SendKuyObject;', '', '/**'];

        foreach ($properties as $property => $types) {
            $lines[] = ' * @property-read '.$this->union($types).' $'.$property;
        }

        $lines[] = ' */';
        $lines[] = 'final class '.$class.' extends SendKuyObject {}';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, string>  $map
     */
    private function renderMap(array $map): string
    {
        $lines = [
            '<?php',
            '',
            'namespace SendKuy\\Objects;',
            '',
            'final class ObjectTypes',
            '{',
            '    /** @var array<string, class-string<\\SendKuy\\SendKuyObject>> */',
            '    public const MAP = [',
        ];

        foreach ($map as $const => $class) {
            $lines[] = "        '{$const}' => {$class}::class,";
        }

        $lines[] = '    ];';
        $lines[] = '}';

        return implode("\n", $lines)."\n";
    }

    private function refName(string $ref): string
    {
        return substr($ref, strrpos($ref, '/') + 1);
    }
}
