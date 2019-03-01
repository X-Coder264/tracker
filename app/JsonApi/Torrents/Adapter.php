<?php

declare(strict_types=1);

namespace App\JsonApi\Torrents;

use App\Models\Torrent;
use App\JsonApi\OffsetStrategy;
use Illuminate\Support\Collection;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $defaultPagination = [
        'offset' => 0,
    ];

    public function __construct(OffsetStrategy $paging)
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
