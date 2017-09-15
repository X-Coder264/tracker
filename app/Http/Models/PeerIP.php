<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PeerIP extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'peers_ip';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
