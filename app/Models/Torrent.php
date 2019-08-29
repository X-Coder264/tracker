<?php

declare(strict_types=1);

namespace App\Models;

use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

/**
 * @property User $uploader
 * @property Collection|TorrentInfoHash[] $infoHashes
 * @property Collection|Peer[] $peers
 * @property Collection|TorrentComment[] $comments
 * @property TorrentCategory $category
 * @property Collection|Snatch[] $snatches
 * @property int $views_count
 */
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

    public function sluggable(): array
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
     */
    public function getSizeAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Scope a query to only include dead torrents.
     */
    public function scopeDead(Builder $query): Builder
    {
        return $query->where('seeders', '=', 0);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function infoHashes(): HasMany
    {
        return $this->hasMany(TorrentInfoHash::class);
    }

    public function peers(): HasMany
    {
        return $this->hasMany(Peer::class)->select(
            ['id', 'torrent_id', 'user_id', 'uploaded', 'downloaded', 'userAgent', 'seeder', 'updated_at']
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TorrentComment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TorrentCategory::class);
    }

    public function snatches(): HasMany
    {
        return $this->hasMany(Snatch::class);
    }
}
