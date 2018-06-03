<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

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
    protected $relationships = [
        'torrents',
        'locale' => 'language',
    ];
}
