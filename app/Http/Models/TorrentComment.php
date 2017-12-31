<?php

declare(strict_types=1);

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

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

    /**
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => ['comment'],
            ],
        ];
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'slug']);
    }

    /**
     * @return BelongsTo
     */
    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class)->select(['id', 'name', 'slug']);
    }
}
