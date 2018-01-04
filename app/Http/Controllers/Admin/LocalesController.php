<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Models\Locale;
use App\JsonApi\Users\Hydrator;
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;

class LocalesController extends EloquentController
{
    /**
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Locale(), $hydrator);
    }
}
