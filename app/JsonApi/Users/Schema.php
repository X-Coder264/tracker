<?php

namespace App\JsonApi\Users;

use App\Http\Models\User;
use App\JsonApi\ResourceTypes;
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
        'timezone',
    ];

    /**
     * @param User  $resource
     * @param bool  $isPrimary
     * @param array $includeRelationships
     *
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'torrents' => [
                self::DATA => $resource->torrents,
            ],
            'locale' => [
                self::DATA => $resource->language,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIncludePaths()
    {
        return [
            'torrents', 'locale',
        ];
    }
}
