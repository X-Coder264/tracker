<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

use App\Http\Models\User;
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
     * @param OffsetStrategy $paging
     */
    public function __construct(OffsetStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new User(), $paging);
    }

    /**
     * Apply the supplied filters to the builder instance.
     *
     * @param Builder    $builder
     * @param Collection $filters
     */
    protected function filter(Builder $builder, Collection $filters)
    {
        if ($filters->has('name')) {
            $builder->where('users.name', '=', $filters->get('name'));
        }

        if ($filters->has('email')) {
            $builder->where('users.email', '=', $filters->get('email'));
        }

        if ($filters->has('timezone')) {
            $builder->where('users.timezone', '=', $filters->get('timezone'));
        }

        if ($filters->has('slug')) {
            $builder->where('users.slug', '=', $filters->get('slug'));
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

    /**
     * Add eager loading to the query.
     *
     * @param Builder    $builder
     * @param Collection $includePaths
     */
    protected function with(Builder $builder, Collection $includePaths)
    {
        if (true === $includePaths->isEmpty()) {
            $builder->with(['torrents', 'language']);
        } else {
            if ($includePaths->contains('torrents')) {
                $builder->with('torrents');
            }
            if ($includePaths->contains('locale')) {
                $builder->with('language');
            }
        }
    }
}
