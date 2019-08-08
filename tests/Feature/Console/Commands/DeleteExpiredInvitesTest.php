<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use App\Models\Invite;
use Illuminate\Support\Collection;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\DeleteExpiredInvitesCommand;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DeleteExpiredInvitesTest extends TestCase
{
    use DatabaseTransactions;

    public function testExpiredInvitesGetDeleted(): void
    {
        $this->withoutExceptionHandling();

        $invite = factory(Invite::class)->create();
        $expiredInvite = factory(Invite::class)->states('expired')->create();

        $this->artisan(DeleteExpiredInvitesCommand::class);

        $this->assertSame(1, Invite::count());
        $this->assertNull(Invite::find($expiredInvite->id));
        $this->assertInstanceOf(Invite::class, Invite::find($invite->id));
    }

    public function testTheCommandIsScheduledProperly(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = (new Collection($schedule->events()))->filter(function (Event $event) {
            return stripos($event->command, 'invites:delete-expired');
        });

        if (1 !== $events->count()) {
            $this->fail(sprintf('The command %s was not scheduled.', DeleteExpiredInvitesCommand::class));
        }

        $events->each(function (Event $event) {
            $this->assertSame('0 * * * *', $event->getExpression());
        });
    }
}
