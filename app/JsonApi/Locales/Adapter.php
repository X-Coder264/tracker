<?php

namespace App\JsonApi\Locales;

use App\Http\Models\Locale;
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
        parent::__construct(new Locale(), $paging);
    }

    /**
     * @inheritdoc
     */
    protected function filter(Builder $builder, Collection $filters)
    {
    }

    /**
     * @inheritdoc
     */
    protected function isSearchOne(Collection $filters)
    {
        return $filters->has('id');
    }
}
