<?php

namespace App\JsonApi\Torrents;

use App\Http\Models\Torrent;
use App\JsonApi\ResourceTypes;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;

class Validators extends AbstractValidatorProvider
{
    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::TORRENT;

    /**
     * @var array
     */
    protected $queryRules = [
        'filter.name' => 'string|min:1',
        'filter.slug' => 'sometimes|required|alpha_dash',
        'page.number' => 'integer|min:1',
        'page.size' => 'integer|between:1,50',
    ];

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
        'title',
        'slug',
    ];

    /**
     * @var array
     */
    protected $allowedFilteringParameters = [
        'id',
        'name',
        'slug',
    ];

    /**
     * {@inheritdoc}
     */
    protected function attributeRules($record = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
    }
}
