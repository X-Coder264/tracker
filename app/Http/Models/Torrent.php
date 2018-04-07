<?php

declare(strict_types=1);

namespace App\Http\Models;

use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class Torrent extends Model
{
    use Sluggable;
    use SluggableScopeHelpers;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => ['name'],
            ],
        ];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the torrent's size.
     *
     * @param $value
     *
     * @return string
     */
    public function getSizeAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * @return BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'slug']);
    }

    /**
     * @return HasMany
     */
    public function peers(): HasMany
    {
        return $this->hasMany(Peer::class)->select(
            ['id', 'torrent_id', 'user_id', 'uploaded', 'downloaded', 'userAgent', 'seeder', 'updated_at']
        );
    }

    /**
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TorrentComment::class);
    }
}
