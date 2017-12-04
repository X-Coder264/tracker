<?php

namespace App\Http\Controllers\Admin;

use App\Http\Models\Locale;
use App\JsonApi\User\Hydrator;
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;

class LocaleController extends EloquentController
{
    /**
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Locale(), $hydrator);
    }
}
