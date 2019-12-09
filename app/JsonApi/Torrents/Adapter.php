<?php

declare(strict_types=1);

namespace App\JsonApi\Torrents;

use App\Models\Torrent;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'id';

    protected $defaultPagination = ['number' => 1];

    public function __construct(StandardStrategy $paging)
    {
        $paging->withMetaKey(null);

        parent::__construct(new Torrent(), $paging);
    }

    protected function filter($query, Collection $filters)
    {
        if ($filters->has('name')) {
            $query->where('torrents.name', '=', $filters->get('name'));
        }

        if ($filters->has('uploader')) {
            $query->where('torrents.uploader_id', '=', $filters->get('uploader'));
        }

        if ($filters->has('minimumSize')) {
            $query->where('torrents.size', '>', (int) $filters->get('minimumSize') * 1024 * 1024);
        }

        if ($filters->has('maximumSize')) {
            $query->where('torrents.size', '<', (int) $filters->get('maximumSize') * 1024 * 1024);
        }

        if ($filters->has('slug')) {
            $query->where('torrents.slug', '=', $filters->get('slug'));
        }
    }
}
