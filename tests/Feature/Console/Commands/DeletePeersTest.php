<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\DeletePeers;
use App\Models\Peer;
use App\Models\PeerIP;
use App\Models\PeerVersion;
use App\Models\Torrent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DeletePeersTest extends TestCase
{
    use DatabaseTransactions;

    public function testObsoletePeersGetDeleted(): void
    {
        $this->withoutExceptionHandling();

        $userOne = factory(User::class)->create();
        $userTwo = factory(User::class)->create();

        $torrent = factory(Torrent::class)->create(['seeders' => 2, 'leechers' => 2]);

        /** @var Repository $cache */
        $cache = $this->app->make(Repository::class);

        $cache->put('torrent.' . $torrent->id, 'test', 10);
        $cache->put('user.' . $userOne->id . '.peers', 'test', 10);
        $cache->put('user.' . $userTwo->id . '.peers', 'test', 10);

        $obsoletePeerOne = factory(Peer::class)->states('v1', 'seeder')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id' => $userOne->id,
                'updated_at' => CarbonImmutable::now()->subMinutes(config('tracker.announce_interval') + 11),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $obsoletePeerOne->id]);

        $obsoletePeerOne = factory(Peer::class)->states('v1', 'leecher')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id' => $userTwo->id,
                'updated_at' => CarbonImmutable::now()->subMinutes(config('tracker.announce_interval') + 11),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $obsoletePeerOne->id]);

        $nonObsoletePeerOne = factory(Peer::class)->states('v1', 'seeder')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id' => $userTwo->id,
                'updated_at' => CarbonImmutable::now()->subMinutes(config('tracker.announce_interval') + 9),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $nonObsoletePeerOne->id]);

        $nonObsoletePeerTwo = factory(Peer::class)->states('v1', 'leecher')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id' => $userOne->id,
                'updated_at' => CarbonImmutable::now()->subMinutes(config('tracker.announce_interval') + 9),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $nonObsoletePeerTwo->id]);

        $this->artisan(DeletePeers::class)->expectsOutput('Deleted obsolete peers: 2');

        $this->assertSame(2, Peer::count());
        $this->assertSame(2, PeerVersion::count());
        $this->assertSame(2, PeerIP::count());

        $freshTorrent = $torrent->fresh();
        $this->assertSame(1, $freshTorrent->seeders);
        $this->assertSame(1, $freshTorrent->leechers);

        try {
            Peer::findOrFail($nonObsoletePeerOne->id);
            PeerVersion::where('peer_id', '=', $nonObsoletePeerOne->id)->firstOrFail();
            PeerIP::where('peer_id', '=', $nonObsoletePeerOne->id)->firstOrFail();
            Peer::findOrFail($nonObsoletePeerTwo->id);
            PeerVersion::where('peer_id', '=', $nonObsoletePeerTwo->id)->firstOrFail();
            PeerIP::where('peer_id', '=', $nonObsoletePeerTwo->id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            $this->fail('This peer and its related data should not have been deleted.');
        }

        $this->assertFalse($cache->has('torrent.' . $torrent->id));
        $this->assertFalse($cache->has('user.' . $userOne->id . '.peers'));
        $this->assertFalse($cache->has('user.' . $userTwo->id . '.peers'));
    }

    public function testTheCommandIsScheduledProperly(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = (new Collection($schedule->events()))->filter(function (Event $event) {
            return stripos($event->command, 'peers:delete');
        });

        if (1 !== $events->count()) {
            $this->fail(sprintf('The command %s was not scheduled.', DeletePeers::class));
        }

        $events->each(function (Event $event) {
            $this->assertSame('*/15 * * * *', $event->getExpression());
        });
    }
}
