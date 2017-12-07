<?php

namespace App\JsonApi\Torrents;

use App\Http\Models\Torrent;
use App\JsonApi\ResourceTypes;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
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
        'slug',
    ];

    /**
     * @param object $resource
     * @param bool   $isPrimary
     * @param array  $includeRelationships
     *
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        if (!$resource instanceof Torrent) {
            throw new RuntimeException('Expecting a torrent model.');
        }

        return [
            'uploader' => [
                self::DATA => $resource->uploader,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getIncludePaths()
    {
        return [
            'uploader',
        ];
    }
}
