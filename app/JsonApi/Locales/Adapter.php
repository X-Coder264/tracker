<?php

namespace App\JsonApi\Locales;

use App\Http\Models\Locale;
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
        parent::__construct(new Locale(), $paging);
    }

    /**
     * {@inheritdoc}
     */
    protected function filter(Builder $builder, Collection $filters)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function isSearchOne(Collection $filters)
    {
        return false;
    }
}
