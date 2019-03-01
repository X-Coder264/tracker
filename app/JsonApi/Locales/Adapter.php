<?php

declare(strict_types=1);

namespace App\JsonApi\Locales;

use App\Models\Locale;
use App\JsonApi\OffsetStrategy;
use Illuminate\Support\Collection;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
{
    /**
     * @var array
     */
    protected $defaultPagination = [
        'offset' => 0,
    ];

    public function __construct(OffsetStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new Locale(), $paging);
    }

    protected function filter($query, Collection $filters)
    {
    }
}
