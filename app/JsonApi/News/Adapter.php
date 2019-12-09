<?php

declare(strict_types=1);

namespace App\JsonApi\News;

use App\Models\News;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'id';

    protected $defaultPagination = ['number' => 1];

    protected $defaultSort = ['-id'];

    private Guard $guard;

    public function __construct(StandardStrategy $paging, Guard $guard)
    {
        $paging->withMetaKey(null);

        parent::__construct(new News(), $paging);

        $this->guard = $guard;
    }

    /**
     * @param Builder $query
     */
    protected function filter($query, Collection $filters)
    {
        if ($filters->has('subject')) {
            $query->where('subject', '=', $filters->get('subject'));
        }

        if ($filters->has('authorId')) {
            $query->where('user_id', '=', $filters->get('authorId'));
        }
    }

    protected function creating(News $news, ResourceObject $resource): void
    {
        $news->author()->associate($this->guard->user());
    }
}
