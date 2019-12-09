<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\PrivateMessages\Thread;
use App\Notifications\ResetPassword;
use Carbon\CarbonImmutable;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $passkey
 * @property Collection|Torrent[] $torrents
 * @property Locale $language
 * @property Collection|Snatch[] $snatches
 * @property Collection|Thread[] $threads
 * @property Collection|News[] $news
 * @property Collection|Invite[] $invites
 * @property int|null $inviter_user_id
 * @property User|null $inviter
 * @property CarbonImmutable|null $last_seen_at
 * @property int $invites_amount
 * @property Collection|User[] $invitees
 * @property bool $is_two_factor_enabled
 * @property string $two_factor_secret_key
 */
class User extends Authenticatable
{
    use Notifiable, Sluggable, SluggableScopeHelpers, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'timezone', 'locale_id', 'torrents_per_page', 'banned', 'last_seen_at',
        'inviter_user_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'banned' => 'bool',
        'is_two_factor_enabled' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'last_seen_at',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name'],
            ],
        ];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get the user's uploaded amount.
     *
     * @param $value
     */
    public function getUploadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Get the user's downloaded amount.
     *
     * @param $value
     */
    public function getDownloadedAttribute($value): string
    {
        return SizeFormatter::getFormattedSize((int) $value);
    }

    /**
     * Scope a query to only include banned users.
     */
    public function scopeBanned(Builder $query): Builder
    {
        return $query->where('banned', '=', true);
    }

    public function torrents(): HasMany
    {
        return $this->hasMany(Torrent::class, 'uploader_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_id');
    }

    public function snatches(): HasMany
    {
        return $this->hasMany(Snatch::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function inviter(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'inviter_user_id');
    }

    public function invitees(): HasMany
    {
        return $this->hasMany(self::class, 'inviter_user_id');
    }
}
