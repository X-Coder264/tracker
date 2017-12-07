<?php

namespace App\JsonApi\Users;

use App\Http\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;

class Adapter extends EloquentAdapter
{
    /**
     * @var array
     */
    protected $defaultPagination = [
        'number' => 1,
    ];

    /**
     * Adapter constructor.
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new User(), $paging);
    }

    /**
     * {@inheritdoc}
     */
    protected function filter(Builder $builder, Collection $filters)
    {
        if ($filters->has('name')) {
            $builder->where('users.name', '=', $filters->get('name'));
        }

        if ($filters->has('slug')) {
            $builder->where('users.slug', $filters->get('slug'));
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
