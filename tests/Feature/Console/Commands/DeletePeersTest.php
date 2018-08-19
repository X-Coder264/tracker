<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Peer;
use App\Models\PeerIP;
use App\Models\PeerVersion;
use App\Console\Commands\DeletePeers;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeletePeersTest extends TestCase
{
    use RefreshDatabase;

    public function testObsoletePeersGetDeleted(): void
    {
        $this->withoutExceptionHandling();

        $obsoletePeer = factory(Peer::class)->states('v1')->create(
            ['updated_at' => Carbon::now()->subMinutes(config('tracker.announce_interval') + 6)]
        );
        factory(PeerIP::class)->create(['peerID' => $obsoletePeer->id]);
        $nonObsoletePeer = factory(Peer::class)->states('v1')->create(
            ['updated_at' => Carbon::now()->subMinutes(config('tracker.announce_interval') + 4)]
        );
        factory(PeerIP::class)->create(['peerID' => $nonObsoletePeer->id]);

        $this->artisan(DeletePeers::class);

        $this->assertSame(1, Peer::count());
        $this->assertSame(1, PeerVersion::count());
        $this->assertSame(1, PeerIP::count());

        try {
            Peer::findOrFail($nonObsoletePeer->id);
            PeerVersion::where('peerID', '=', $nonObsoletePeer->id)->firstOrFail();
            PeerIP::where('peerID', '=', $nonObsoletePeer->id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            $this->fail('This peer and its related data should not have been deleted.');
        }
    }

    public function testTheCommandIsScheduledProperly(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = collect($schedule->events())->filter(function (Event $event) {
            return stripos($event->command, 'peers:delete');
        });

        if (1 !== $events->count()) {
            $this->fail('The command DeletePeers was not scheduled.');
        }

        $events->each(function (Event $event) {
            $this->assertSame('*/15 * * * *', $event->getExpression());
        });
    }
}
