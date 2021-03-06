<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

use App\Models\User;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\BelongsTo;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'id';

    protected $defaultPagination = ['number' => 1];

    protected $includePaths = ['locale' => 'language'];

    private Hasher $hasher;

    public function __construct(StandardStrategy $paging, Hasher $hasher)
    {
        $paging->withMetaKey(null);

        parent::__construct(new User(), $paging);

        $this->hasher = $hasher;
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

    protected function creating(User $user, ResourceObject $resource): void
    {
        $user->password = $this->hasher->make($resource['password']);
    }
}
