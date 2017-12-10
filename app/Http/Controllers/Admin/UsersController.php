<?php

namespace App\Http\Controllers\Admin;

use App\Http\Models\User;
use App\JsonApi\Users\Hydrator;
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;

class UsersController extends EloquentController
{
    /**
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new User(), $hydrator);
    }
}