<?php

namespace App\JsonApi\Locales;

use App\Http\Models\Locale;
use App\JsonApi\ResourceTypes;
use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;

class Schema extends EloquentSchema
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::LOCALE;

    /**
     * @var array
     */
    protected $attributes = [
        'locale',
        'localeShort',
    ];

    /**
     * @param Locale $resource
     * @param bool   $isPrimary
     * @param array  $includeRelationships
     *
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [];
    }
}
