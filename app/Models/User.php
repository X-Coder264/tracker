<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use App\Models\PrivateMessages\Thread;
use Spatie\Permission\Traits\HasRoles;
use Facades\App\Services\SizeFormatter;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, Sluggable, SluggableScopeHelpers, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'timezone', 'locale_id', 'torrents_per_page', 'banned', 'last_seen_at',
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
     * Set the user's password.
     */
    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = Hash::make($password);
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
}
