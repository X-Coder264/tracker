<?php

declare(strict_types=1);

namespace App\Models;

use Facades\App\Services\SecondsDurationFormatter;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Get the snatch's uploaded attribute.
     *
     * @param $value
     */
    public function getUploadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Get the snatch's downloaded attribute.
     *
     * @param $value
     */
    public function getDownloadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Get the snatch's left attribute.
     *
     * @param $value
     */
    public function getLeftAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Get the snatch's seed time attribute.
     *
     * @param $value
     */
    public function getSeedTimeAttribute($value): string
    {
        return SecondsDurationFormatter::format((int) $value);
    }

    /**
     * Get the snatch's leech time attribute.
     *
     * @param $value
     */
    public function getLeechTimeAttribute($value): string
    {
        return SecondsDurationFormatter::format((int) $value);
    }

    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
