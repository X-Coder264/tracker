<?php

declare(strict_types=1);

namespace App\JsonApi\Locales;

use App\JsonApi\ResourceTypes;
use App\Models\Locale;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::LOCALE;

    /**
     * @param Locale $resource
     */
    public function getId($resource): string
    {
        return (string) $resource->id;
    }

    /**
     * @param Locale $resource
     */
    public function getAttributes($resource): array
    {
        return [
            'locale' => $resource->locale,
            'locale-short' => $resource->localeShort,
            'created-at' => $resource->created_at->toAtomString(),
            'updated-at' => $resource->updated_at->toAtomString(),
        ];
    }
}
