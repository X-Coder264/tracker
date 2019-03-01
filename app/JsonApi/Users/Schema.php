<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

use App\Models\User;
use App\JsonApi\ResourceTypes;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::USER;

    /**
     * @param User $resource
     */
    public function getId($resource): string
    {
        return (string) $resource->id;
    }

    /**
     * @param User $resource
     */
    public function getAttributes($resource): array
    {
        return [
            'name' => $resource->name,
            'slug' => $resource->slug,
            'email' => $resource->email,
            'timezone' => $resource->timezone,
            'created-at' => $resource->created_at->toAtomString(),
            'updated-at' => $resource->updated_at->toAtomString(),
        ];
    }

    /**
     * @param User $resource
     * @param bool $isPrimary
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
