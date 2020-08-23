<?php

declare(strict_types=1);

namespace App\Models;

use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Builder;
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
     * Get the peer's uploaded attribute.
     *
     * @param $value
     */
    public function getUploadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Get the peer's downloaded attribute.
     *
     * @param $value
     */
    public function getDownloadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Scope a query to only include seeders.
     */
    public function scopeSeeders(Builder $query): Builder
    {
        return $query->where('left', '=', 0);
    }

    /**
     * Scope a query to only include leechers.
     */
    public function scopeLeechers(Builder $query): Builder
    {
        return $query->where('left', '!=', 0);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }

    public function ips(): HasMany
    {
        return $this->hasMany(PeerIP::class, 'peer_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PeerVersion::class, 'peer_id');
    }
}
