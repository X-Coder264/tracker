<?php

declare(strict_types=1);

namespace App\Http\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Torrent extends Model
{
    use Sluggable;
    use SluggableScopeHelpers;

    /**
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
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
     * @return BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id')->select(['id','name', 'slug']);
    }

    /**
     * @return HasMany
     */
    public function peers(): HasMany
    {
        return $this->hasMany(Peer::class)->select(['torrent_id','peer_id', 'ip', 'port', 'seeder']);
    }
}
