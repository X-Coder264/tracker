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
     * Adapter constructor.
     *
     * @param OffsetStrategy $paging
     */
    public function __construct(OffsetStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new Torrent(), $paging);
    }

    /**
     * {@inheritdoc}
     */
    protected function filter(Builder $builder, Collection $filters)
    {
        if ($filters->has('name')) {
            $builder->where('torrents.name', '=', $filters->get('name'));
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
     * {@inheritdoc}
     */
    protected function isSearchOne(Collection $filters)
    {
        return false;
    }
}
