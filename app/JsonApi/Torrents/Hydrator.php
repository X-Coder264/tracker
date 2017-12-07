<?php

namespace App\JsonApi\Torrents;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
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

    /**
     * Called before any hydration occurs.
     *
     * Child classes can overload this method if they need to do any logic pre-hydration.
     *
     * @param ResourceObjectInterface $resource
     * @param $record
     * @return void
     */
    protected function hydrating(ResourceObjectInterface $resource, $record)
    {
        dd($record); die();
        $record->size = 500;
    }
}
