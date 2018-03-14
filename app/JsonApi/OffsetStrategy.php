<?php

declare(strict_types=1);

namespace App\JsonApi;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use CloudCreativity\LaravelJsonApi\Pagination\CreatesPages;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PagingStrategyInterface;

class OffsetStrategy implements PagingStrategyInterface
{
    use CreatesPages;

    /**
     * @var string|null
     */
    protected $pageKey;

    /**
     * @var string|null
     */
    protected $perPageKey;

    /**
     * @var array|null
     */
    protected $columns;

    /**
     * @var string|null
     */
    protected $metaKey;

    /**
     * OffsetStrategy constructor.
     */
    public function __construct()
    {
        $this->metaKey = QueryParametersParserInterface::PARAM_PAGE;
    }

    /**
     * Set the key for the paging meta.
     *
     * Use this to 'nest' the paging meta in a sub-key of the JSON API document's top-level meta object.
     * A string sets the key to use for nesting. Use `null` to indicate no nesting.
     *
     * @param string|null $key
     *
     * @return self
     */
    public function withMetaKey($key): self
    {
        $this->metaKey = $key ?: null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($query, EncodingParametersInterface $parameters): PageInterface
    {
        $pageParameters = new Collection((array) $parameters->getPaginationParameters());
        $paginator = $this->query($query, $pageParameters);

        return $this->createPage($paginator, $parameters);
    }

    /**
     * @param Paginator                   $paginator
     * @param EncodingParametersInterface $parameters
     *
     * @return PageInterface
     */
    protected function createPage(Paginator $paginator, EncodingParametersInterface $parameters): PageInterface
    {
        return app(FactoryInterface::class)->createPage(
            $paginator,
            null,
            null,
            null,
            null,
            $this->createMeta($paginator),
            $this->getMetaKey()
        );
    }

    /**
     * @return string
     */
    protected function getPageKey(): string
    {
        $key = property_exists($this, 'pageKey') ? $this->pageKey : null;

        return $key ?: 'offset';
    }

    /**
     * @return string
     */
    protected function getPerPageKey(): string
    {
        $key = property_exists($this, 'perPageKey') ? $this->perPageKey : null;

        return $key ?: 'limit';
    }

    /**
     * @param Collection $collection
     *
     * @return int
     */
    protected function getPerPage(Collection $collection): int
    {
        return (int) $collection->get($this->getPerPageKey());
    }

    /**
     * Get the default per-page value for the query.
     *
     * If the query is an Eloquent builder, we can pass in `null` as the default,
     * which then delegates to the model to get the default. Otherwise the Laravel
     * standard default is 15.
     *
     * @param $query
     *
     * @return int|null
     */
    protected function getDefaultPerPage($query): ?int
    {
        return $query instanceof EloquentBuilder ? null : 15;
    }

    /**
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->columns ?: ['*'];
    }

    /**
     * @param mixed      $query
     * @param Collection $pagingParameters
     *
     * @return mixed
     */
    protected function query($query, Collection $pagingParameters)
    {
        $pageName = $this->getPageKey();
        $size = $this->getPerPage($pagingParameters) ?: $this->getDefaultPerPage($query);
        $cols = $this->getColumns();
        $perPage = $pagingParameters->get($this->getPerPageKey());
        $offset = $pagingParameters->get($pageName);
        if (null !== $perPage) {
            $x = $offset / $perPage;
            if (is_int($x)) {
                $x++;
            }
            $page = ceil($x) + ('0' === $offset ? 0 : 1);
        } else {
            $page = 1;
        }

        return $query->paginate($size, $cols, $pageName, $page);
    }
}