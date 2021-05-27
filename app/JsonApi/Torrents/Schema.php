<?php

declare(strict_types=1);

namespace App\JsonApi\Torrents;

use App\JsonApi\ResourceTypes;
use App\Models\Torrent;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::TORRENT;

    /**
     * @param Torrent $resource
     */
    public function getId($resource): string
    {
        return (string) $resource->id;
    }

    /**
     * @param Torrent $resource
     */
    public function getAttributes($resource): array
    {
        return [
            'name' => $resource->name,
            'size' => $resource->size,
            'description' => $resource->description,
            'slug' => $resource->slug,
            'created-at' => $resource->created_at->toAtomString(),
            'updated-at' => $resource->updated_at->toAtomString(),
        ];
    }

    /**
     * @param Torrent $resource
     * @param bool    $isPrimary
     *
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        $relationships = [];

        if (! empty($includeRelationships) && in_array('uploader', $includeRelationships, true)) {
            $relationships['uploader'] = [
                self::DATA => function () use ($resource) {
                    return $resource->uploader;
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
            'uploader',
        ];
    }
}
