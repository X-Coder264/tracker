<?php

namespace App\JsonApi\Locale;

use App\Http\Models\Locale;
use App\JsonApi\ResourceTypes;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
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
     * @param object $resource
     * @param bool   $isPrimary
     * @param array  $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        if (!$resource instanceof Locale) {
            throw new RuntimeException('Expecting a locale model.');
        }

        return [];
    }
}
