<?php

namespace App\Http\Controllers\Admin;

use App\Http\Models\Torrent;
use App\JsonApi\Users\Hydrator;
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;

class TorrentsController extends EloquentController
{
    /**
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Torrent(), $hydrator);
    }
}