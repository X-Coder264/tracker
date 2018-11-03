<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Models\Peer;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;

class UserStatsComposer
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

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        if (true === $this->guard->check()) {
            /** @var Collection $peers */
            $peers = $this->cache->remember('user.' . $this->guard->id() . '.peers', 30, function (): Collection {
                return Peer::where('user_id', '=', $this->guard->id())->get();
            });

            if (true === $peers->isEmpty()) {
                $numberOfSeedingTorrents = 0;
                $numberOfLeechingTorrents = 0;
            } else {
                $numberOfSeedingTorrents = $peers->filter(function (Peer $peer): bool {
                    return true === $peer->seeder;
                })->count();

                $numberOfLeechingTorrents = $peers->count() - $numberOfSeedingTorrents;
            }

            $view->with('numberOfSeedingTorrents', $numberOfSeedingTorrents);
            $view->with('numberOfLeechingTorrents', $numberOfLeechingTorrents);
        }
    }
}
