<?php

declare(strict_types=1);

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Snatch extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'finished_at',
    ];
}
