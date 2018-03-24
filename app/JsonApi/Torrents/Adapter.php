<?php

namespace App\JsonApi\Torrents;

use App\Http\Models\Torrent;
use App\JsonApi\OffsetStrategy;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;

class Adapter extends EloquentAdapter
{
    /**
     * @var array
     */
    protected $defaultPagination = [
        'offset' => 0,
    ];

    /**
     *
     * @param OffsetStrategy $paging
     */
    public function __construct(OffsetStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new Torrent(), $paging);
    }

    /**
     * Apply the supplied filters to the builder instance.
     *
     * @param Builder $builder
     * @param Collection $filters
     *
     * @return void
     */
    protected function filter(Builder $builder, Collection $filters)
    {
        if ($filters->has('name')) {
            $builder->where('torrents.name', '=', $filters->get('name'));
        }

        if ($filters->has('uploader')) {
            $builder->where('torrents.uploader_id', '=', $filters->get('uploader'));
        }

        if ($filters->has('minimumSize')) {
            $builder->where('torrents.size', '>', (int) $filters->get('minimumSize') * 1024 * 1024);
        }

        if ($filters->has('maximumSize')) {
            $builder->where('torrents.size', '<', (int) $filters->get('maximumSize') * 1024 * 1024);
        }

        if ($filters->has('slug')) {
            $builder->where('torrents.slug', '=', $filters->get('slug'));
        }
    }

    /**
     * Is this a search for a singleton resource?
     *
     * @param Collection $filters
     *
     * @return bool
     */
    protected function isSearchOne(Collection $filters)
    {
        return false;
    }
}
