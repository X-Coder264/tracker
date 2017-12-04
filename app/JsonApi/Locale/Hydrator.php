<?php

namespace App\JsonApi\Locale;

use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;

class Hydrator extends EloquentHydrator
{
    /**
     * @var array
     */
    protected $attributes = [
        'name',
        'password',
        'email',
        'timezone',
        'slug',
    ];

    /**
     * @var array
     */
    protected $relationships = [];
}
