<?php

declare(strict_types=1);

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorrentComment extends Model
{
    use Sluggable;
    use SluggableScopeHelpers;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'comment', 'torrent_id', 'user_id',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['comment'],
            ],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'slug']);
    }

    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class)->select(['id', 'name', 'slug']);
    }
}
