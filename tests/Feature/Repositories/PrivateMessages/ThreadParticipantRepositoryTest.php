<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories\PrivateMessages;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadParticipant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Repositories\PrivateMessages\ThreadParticipantRepository;

class ThreadParticipantRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetUnreadThreadsForUser(): void
    {
        $user = factory(User::class)->create();

        $timeNow = Carbon::now();
        $threadOne = factory(Thread::class)->create();
        $threadTwo = factory(Thread::class)->create(['updated_at' => Carbon::now()->subDay()]);
        $threadThree = factory(Thread::class)->create(['updated_at' => $timeNow]);
        $threadFour = factory(Thread::class)->create();
        $threadFive = factory(Thread::class)->create();

        $irrelevantUser = factory(User::class)->create();

        factory(ThreadParticipant::class)->create(
            [
                'thread_id' => $threadOne->id,
                'user_id' => $user->id,
                'last_read_at' => null,
            ]
        );

        factory(ThreadParticipant::class)->create(
            [
                'thread_id' => $threadTwo->id,
                'user_id' => $user->id,
                'last_read_at' => Carbon::now(),
            ]
        );

        factory(ThreadParticipant::class)->create(
            [
                'thread_id' => $threadThree->id,
                'user_id' => $user->id,
                'last_read_at' => $timeNow,
            ]
        );

        factory(ThreadParticipant::class)->create(
            [
                'thread_id' => $threadFour->id,
                'user_id' => $irrelevantUser->id,
                'last_read_at' => Carbon::now()->addDay(),
            ]
        );

        factory(ThreadParticipant::class)->create(
            [
                'thread_id' => $threadFive->id,
                'user_id' => $user->id,
                'last_read_at' => Carbon::now()->subSeconds(5),
            ]
        );

        $repository = $this->app->make(ThreadParticipantRepository::class);

        $unreadThreads = $repository->getUnreadThreadsForUser($user->id);

        $this->assertTrue($unreadThreads->contains($threadOne->id));
        $this->assertFalse($unreadThreads->contains($threadTwo->id));
        $this->assertFalse($unreadThreads->contains($threadThree->id));
        $this->assertFalse($unreadThreads->contains($threadFour->id));
        $this->assertTrue($unreadThreads->contains($threadFive->id));
    }
}
