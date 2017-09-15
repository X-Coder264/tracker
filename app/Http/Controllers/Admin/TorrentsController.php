<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Torrent;
use App\JsonApi\Users\Hydrator;
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;

class TorrentsController extends EloquentController
{
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Torrent(), $hydrator);
    }
}
