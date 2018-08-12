<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class TorrentCategory extends Model
{
    use Sluggable;
    use SluggableScopeHelpers;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'imdb' => 'bool',
    ];

    /**
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name'],
            ],
        ];
    }

    /**
     * @return HasMany
     */
    public function torrents(): HasMany
    {
        return $this->hasMany(Torrent::class, 'category_id');
    }
}
