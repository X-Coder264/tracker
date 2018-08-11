<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

use App\Models\User;
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
        $relationships = [];

        if (! empty($includeRelationships) && in_array('torrents', $includeRelationships)) {
            $relationships['torrents'] = [
                self::DATA => function () use ($resource) {
                    return $resource->torrents;
                },
            ];
        }

        if (! empty($includeRelationships) && in_array('locale', $includeRelationships)) {
            $relationships['locale'] = [
                self::DATA => function () use ($resource) {
                    return $resource->language;
                },
            ];
        }

        return $relationships;
    }

    /**
     * Get schema default include paths.
     *
     * @return string[]
     */
    public function getIncludePaths()
    {
        return [
            'torrents', 'locale',
        ];
    }
}
