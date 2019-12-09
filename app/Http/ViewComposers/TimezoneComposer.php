<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Enumerations\Cache;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\View\View;

class TimezoneComposer
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var Repository
     */
    private $cache;

    public function __construct(Guard $guard, Repository $cache)
    {
        $this->guard = $guard;
        $this->cache = $cache;
    }

    public function compose(View $view): void
    {
        $user = $this->cache->remember('user.' . $this->guard->id(), Cache::ONE_DAY, function (): User {
            return User::with(['language', 'torrents'])->findOrFail($this->guard->id());
        });

        $view->with('timezone', $user->timezone);
    }
}
