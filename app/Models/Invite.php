<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property string          $code
 * @property CarbonImmutable $expires_at
 * @property int             $user_id
 * @property User            $user
 */
class Invite extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['expires_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
