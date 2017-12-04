<?php

namespace App\Http\Controllers\Admin;

use App\Http\Models\User;
use App\JsonApi\User\Hydrator;
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;

class UserController extends EloquentController
{
    /**
     *
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new User(), $hydrator);
    }
}
