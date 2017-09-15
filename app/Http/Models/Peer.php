<?php

declare(strict_types=1);

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peer extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return BelongsTo
     */
    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }

    /**
     * @return HasMany
     */
    public function IPs(): HasMany
    {
        return $this->hasMany(PeerIP::class, 'peerID');
    }
}
