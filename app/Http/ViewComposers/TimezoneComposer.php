<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;

class TimezoneComposer
{
    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @param AuthManager  $authManager
     * @param CacheManager $cacheManager
     */
    public function __construct(AuthManager $authManager, CacheManager $cacheManager)
    {
        $this->authManager = $authManager;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Bind data to the view.
     *
     * @param View $view
     */
    public function compose(View $view): void
    {
        $user = $this->cacheManager->remember('user.' . $this->authManager->guard()->id(), 24 * 60, function () {
            return User::with(['language', 'torrents'])->findOrFail($this->authManager->guard()->id());
        });

        $view->with('timezone', $user->timezone);
    }
}
