<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Deleted;
use SendKuy\Objects\Group;
use SendKuy\Objects\GroupMembership;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Groups extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Group>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Group::class, '/groups', $params, $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function create(array $params, array $options = []): Group
    {
        return $this->item(Group::class, 'POST', '/groups', body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Group
    {
        return $this->item(Group::class, 'GET', self::path('/groups/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function update(string $id, array $params, array $options = []): Group
    {
        return $this->item(Group::class, 'PATCH', self::path('/groups/%s', $id), body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function delete(string $id, array $options = []): Deleted
    {
        return $this->item(Deleted::class, 'DELETE', self::path('/groups/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function addContacts(string $id, array $params, array $options = []): GroupMembership
    {
        return $this->item(GroupMembership::class, 'POST', self::path('/groups/%s/contacts', $id), body: $params, options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function removeContacts(string $id, array $params, array $options = []): GroupMembership
    {
        return $this->item(GroupMembership::class, 'DELETE', self::path('/groups/%s/contacts', $id), body: $params, options: $options);
    }
}
