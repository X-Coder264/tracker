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
     *
     * @param OffsetStrategy $paging
     */
    public function __construct(OffsetStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new Locale(), $paging);
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
