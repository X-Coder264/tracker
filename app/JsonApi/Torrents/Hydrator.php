<?php

namespace App\JsonApi\Torrents;

use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;

class Hydrator extends EloquentHydrator
{
    /**
     * @var array
     */
    protected $attributes = [
        'name',
        'slug',
    ];

    /**
     * @var array
     */
    protected $relationships = [
        'uploader',
    ];
}
