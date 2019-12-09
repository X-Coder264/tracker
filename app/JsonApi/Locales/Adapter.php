<?php

declare(strict_types=1);

namespace App\JsonApi\Locales;

use App\Models\Locale;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{
    protected $defaultPagination = ['number' => 1];

    public function __construct(StandardStrategy $paging)
    {
        $paging->withMetaKey(null);

        parent::__construct(new Locale(), $paging);
    }

    protected function filter($query, Collection $filters)
    {
    }
}
