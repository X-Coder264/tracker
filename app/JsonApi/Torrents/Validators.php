<?php

namespace App\JsonApi\Torrents;

use App\Http\Models\User;
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
        'email'
    ];

    /**
     * @inheritdoc
     */
    protected function attributeRules($record = null)
    {
        /** @var User $record */

        // The JSON API spec says the client does not have to send all attributes for an update request, so
        // if the record already exists we need to include a 'sometimes' before required.
        $required = $record ? 'sometimes|required' : 'required';

        return [
            'name' => "$required|string|between:1,255",
        ];
    }

    /**
     * @inheritdoc
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        //$relationships->hasOne('author', 'people', is_null($record), false);
    }
}
