<?php

declare(strict_types=1);

namespace App\JsonApi\News;

use App\Models\News;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{
    /**
     * @var string
     */
    protected $resourceType = 'news';

    /**
     * @param News $resource
     *
     * @return string
     */
    public function getId($resource)
    {
        return (string) $resource->id;
    }

    /**
     * @param News $resource
     *
     * @return array
     */
    public function getAttributes($resource)
    {
        return [
            'subject' => $resource->subject,
            'text' => $resource->text,
            'created_at' => $resource->created_at->toAtomString(),
            'updated_at' => $resource->updated_at->toAtomString(),
        ];
    }

    /**
     * Get resource links.
     *
     * @param News  $resource
     * @param bool  $isPrimary
     * @param array $includeRelationships A list of relationships that will be included as full resources.
     *
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::SHOW_DATA => isset($includeRelationships['author']),
                self::DATA => function () use ($resource) {
                    return $resource->author;
                },
            ],
        ];
    }
}
