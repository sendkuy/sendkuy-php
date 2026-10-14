<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Contact;
use SendKuy\Objects\ContactAttribute;
use SendKuy\Objects\Job;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Contacts extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Contact>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Contact::class, '/contacts', $params, $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function create(array $params, array $options = []): Contact
    {
        return $this->item(Contact::class, 'POST', '/contacts', body: $params, options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function bulkImport(array $params, array $options = []): Job
    {
        return $this->item(Job::class, 'POST', '/contacts/bulk', body: $params, options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function lookup(array $params, array $options = []): Contact
    {
        return $this->item(Contact::class, 'GET', '/contacts/lookup', $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Contact
    {
        return $this->item(Contact::class, 'GET', self::path('/contacts/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function update(string $id, array $params, array $options = []): Contact
    {
        return $this->item(Contact::class, 'PATCH', self::path('/contacts/%s', $id), body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function delete(string $id, array $options = []): Contact
    {
        return $this->item(Contact::class, 'DELETE', self::path('/contacts/%s', $id), options: $options);
    }

    /**
     * @param  Options  $options
     * @return list<ContactAttribute>
     */
    public function listAttributes(array $options = []): array
    {
        return $this->items(ContactAttribute::class, 'GET', '/contact-attributes', options: $options);
    }
}
