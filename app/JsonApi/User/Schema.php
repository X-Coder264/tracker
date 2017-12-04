<?php

namespace App\JsonApi\User;

use App\Http\Models\User;
use App\JsonApi\ResourceTypes;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;

class Schema extends EloquentSchema
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::USER;

    /**
     * @var array
     */
    protected $attributes = [
        'name',
        'slug',
        'email',
    ];

    /**
     * @param object $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        if (!$resource instanceof User) {
            throw new RuntimeException('Expecting a user model.');
        }

        return [
            /*'torrents' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => $resource->torrents
            ],*/
            'locale' => [
                self::DATA => $resource->language,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getIncludePaths()
    {
        return [
            'locale',
        ];
    }
}
