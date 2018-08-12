<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Models\Peer;
use Illuminate\View\View;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Collection;

class UserStatsComposer
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
        if (true === $this->authManager->guard()->check()) {
            /** @var Collection $peers */
            $peers = $this->cacheManager->remember('user.' . $this->authManager->guard()->id() . '.peers', 30, function () {
                return Peer::where('user_id', '=', $this->authManager->guard()->id())->get();
            });

            if (true === $peers->isEmpty()) {
                $numberOfSeedingTorrents = 0;
                $numberOfLeechingTorrents = 0;
            } else {
                $numberOfSeedingTorrents = $peers->filter(function (Peer $peer) {
                    return true === $peer->seeder;
                })->count();

                $numberOfLeechingTorrents = $peers->count() - $numberOfSeedingTorrents;
            }

            $view->with('numberOfSeedingTorrents', $numberOfSeedingTorrents);
            $view->with('numberOfLeechingTorrents', $numberOfLeechingTorrents);
        }
    }
}
