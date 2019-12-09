<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Enumerations\Cache;
use App\Models\Peer;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class UserStatsComposer
{
    private Guard $guard;
    private Repository $cache;

    public function __construct(Guard $guard, Repository $cache)
    {
        $this->guard = $guard;
        $this->cache = $cache;
    }

    public function compose(View $view): void
    {
        if (true === $this->guard->check()) {
            /** @var Collection $peers */
            $peers = $this->cache->remember('user.' . $this->guard->id() . '.peers', Cache::THIRTY_MINUTES, function (): Collection {
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
