<?php

namespace App\JsonApi\User;

use App\Http\Models\User;
use App\JsonApi\ResourceTypes;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = ResourceTypes::USER;

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
            'password' => "$required|string|between:1,255",
            'email' => "$required|string|email",
            'timezone' => "$required|string|timezone",
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
