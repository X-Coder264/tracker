<?php

namespace App\JsonApi\Torrents;

use App\Http\Models\Torrent;
use App\JsonApi\ResourceTypes;
use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;

class Schema extends EloquentSchema
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::TORRENT;

    /**
     * @var array
     */
    protected $attributes = [
        'name',
        'size',
        'description',
        'slug',
    ];

    /**
     * @param Torrent $resource
     * @param bool    $isPrimary
     * @param array   $includeRelationships
     *
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'uploader' => [
                self::DATA => $resource->uploader,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIncludePaths()
    {
        return [
            'uploader',
        ];
    }
}
