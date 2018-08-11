<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
     *
     * @param Builder $query
     * @param string  $name
     *
     * @return Builder
     */
    public function scopeGetConfigurationValue(Builder $query, string $name): Builder
    {
        return $query->where('name', '=', $name);
    }
}
