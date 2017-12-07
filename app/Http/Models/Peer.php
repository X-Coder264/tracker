<?php

declare(strict_types=1);

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Services\SizeFormattingService;
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
     * Get the peer's uploaded attribute.
     *
     * @param $value
     *
     * @return string
     */
    public function getUploadedAttribute($value)
    {
        $formatter = new SizeFormattingService();

        return $formatter->getFormattedSize($value);
    }

    /**
     * Get the peer's downloaded attribute.
     *
     * @param $value
     *
     * @return string
     */
    public function getDownloadedAttribute($value)
    {
        $formatter = new SizeFormattingService();

        return $formatter->getFormattedSize($value);
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
