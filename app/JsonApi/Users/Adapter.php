<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

use App\Models\User;
use App\JsonApi\OffsetStrategy;
use Illuminate\Support\Collection;
use CloudCreativity\LaravelJsonApi\Eloquent\BelongsTo;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $defaultPagination = [
        'offset' => 0,
    ];

    protected $includePaths = ['locale' => 'language'];

    public function __construct(OffsetStrategy $paging)
    {
        $paging->withMetaKey(null);
        parent::__construct(new User(), $paging);
    }

    protected function filter($query, Collection $filters)
    {
        if ($filters->has('name')) {
            $query->where('users.name', '=', $filters->get('name'));
        }

        if ($filters->has('email')) {
            $query->where('users.email', '=', $filters->get('email'));
        }

        if ($filters->has('timezone')) {
            $query->where('users.timezone', '=', $filters->get('timezone'));
        }

        if ($filters->has('slug')) {
            $query->where('users.slug', '=', $filters->get('slug'));
        }
    }

    protected function with($query, EncodingParametersInterface $parameters)
    {
        $includePaths = $parameters->getIncludePaths() ?? [];

        $includePaths = new Collection($includePaths);

        if (true === $includePaths->isEmpty()) {
            $query->with(['torrents', 'language']);
        } else {
            if ($includePaths->contains('torrents')) {
                $query->with('torrents');
            }
            if ($includePaths->contains('locale')) {
                $query->with('language');
            }
        }
    }

    protected function locale(): BelongsTo
    {
        return $this->belongsTo('language');
    }
}
