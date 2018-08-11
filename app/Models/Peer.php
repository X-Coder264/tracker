<?php

declare(strict_types=1);

namespace App\Models;

use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peer extends Model
{
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
        'seeder' => 'bool',
    ];

    /**
     * Get the peer's uploaded attribute.
     *
     * @param $value
     *
     * @return string
     */
    public function getUploadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Get the peer's downloaded attribute.
     *
     * @param $value
     *
     * @return string
     */
    public function getDownloadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
