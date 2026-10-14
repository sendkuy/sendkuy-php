<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Deleted;
use SendKuy\Objects\Template;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Templates extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Template>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Template::class, '/templates', $params, $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function create(array $params, array $options = []): Template
    {
        return $this->item(Template::class, 'POST', '/templates', body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Template
    {
        return $this->item(Template::class, 'GET', self::path('/templates/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function update(string $id, array $params, array $options = []): Template
    {
        return $this->item(Template::class, 'PATCH', self::path('/templates/%s', $id), body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function delete(string $id, array $options = []): Deleted
    {
        return $this->item(Deleted::class, 'DELETE', self::path('/templates/%s', $id), options: $options);
    }
}
