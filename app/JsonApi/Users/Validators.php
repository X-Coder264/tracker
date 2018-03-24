<?php

namespace App\JsonApi\Users;

use App\Http\Models\User;
use App\JsonApi\ResourceTypes;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;

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
        'page.offset' => 'integer|min:0',
        'page.limit' => 'integer|between:1,60',
    ];

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'id',
        'created-at',
        'updated-at',
    ];

    /**
     * @var string[]
     */
    protected $allowedFilteringParameters = [
        'id',
        'name',
        'timezone',
        'slug',
        'email',
    ];

    /**
     * @var string[]
     */
    protected $allowedIncludePaths = [
        'torrents', 'locale',
    ];

    /**
     * {@inheritdoc}
     */
    protected function attributeRules($record = null)
    {
        /** @var User $record */

        // The JSON API spec says the client does not have to send all attributes for an update request, so
        // if the record already exists we need to include a 'sometimes' before required.
        $required = $record ? 'sometimes|required' : 'required';

        return [
            'name' => "$required|string|between:1,255",
            'password' => "$required|string|between:8,255",
            'email' => "$required|string|email",
            'timezone' => "$required|string|timezone",
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        $relationships->hasOne('locale', ResourceTypes::LOCALE, is_null($record), false);
    }
}
