<?php

declare(strict_types=1);

namespace App\Models;

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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_ipv6' => 'bool',
    ];
}
