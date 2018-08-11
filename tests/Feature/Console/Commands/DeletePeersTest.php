<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Peer;
use App\Console\Commands\DeletePeers;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeletePeersTest extends TestCase
{
    use RefreshDatabase;

    public function testObsoletePeersGetDeleted()
    {
        factory(Peer::class)->create(['updated_at' => Carbon::now()->subMinutes(50)]);
        $nonObsoletePeer = factory(Peer::class)->create(['updated_at' => Carbon::now()->subMinutes(40)]);

        $this->artisan(DeletePeers::class);

        $this->assertSame(1, Peer::count());

        try {
            Peer::findOrFail($nonObsoletePeer->id);
        } catch (ModelNotFoundException $exception) {
            $this->fail('This peer should not have been deleted.');
        }
    }

    public function testTheCommandIsScheduledProperly()
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
