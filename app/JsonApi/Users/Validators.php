<?php

declare(strict_types=1);

namespace App\JsonApi\Users;

use App\Models\User;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
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
     * @param User|null $record
     */
    protected function rules($record, array $data): array
    {
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

    protected function queryRules(): array
    {
        return [
            'page.number' => 'integer|min:1',
            'page.size' => 'integer|between:1,60',
        ];
    }
}
