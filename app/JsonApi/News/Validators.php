<?php

declare(strict_types=1);

namespace App\JsonApi\News;

use App\Models\News;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
    /**
     * @var string[]|null
     */
    protected $allowedIncludePaths = ['author'];

    /**
     * @var string[]|null
     */
    protected $allowedSortParameters = [
        'id',
        'updated_at',
    ];

    /**
     * @param News|null $record
     */
    protected function rules($record = null): array
    {
        return [
            'subject' => 'required|string|min:5',
            'text' => 'required|string|min:30',
        ];
    }

    protected function queryRules(): array
    {
        return [];
    }
}
