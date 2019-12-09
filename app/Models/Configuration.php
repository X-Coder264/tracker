<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property mixed $value
 */
class Configuration extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'configuration';

    /**
     * Scope a query to only include the wanted configuration value.
     */
    public function scopeGetConfigurationValue(Builder $query, string $name): Builder
    {
        return $query->where('name', '=', $name);
    }
}
