<?php

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
     * Adapter constructor.
     *
     * @param OffsetStrategy $paging
     */
    public function __construct(OffsetStrategy $paging)
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
     * {@inheritdoc}
     */
    protected function isSearchOne(Collection $filters)
    {
        return false;
    }
}
