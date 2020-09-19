<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories\PrivateMessages;

use App\Repositories\PrivateMessages\ThreadParticipantRepository;
use Carbon\Carbon;
use Database\Factories\ThreadFactory;
use Database\Factories\ThreadParticipantFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ThreadParticipantRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetUnreadThreadsForUser(): void
    {
        $user = UserFactory::new()->create();

        $timeNow = Carbon::now();
        $threadOne = ThreadFactory::new()->create();
        $threadTwo = ThreadFactory::new()->create(['updated_at' => Carbon::now()->subDay()]);
        $threadThree = ThreadFactory::new()->create(['updated_at' => $timeNow]);
        $threadFour = ThreadFactory::new()->create();
        $threadFive = ThreadFactory::new()->create();

        $irrelevantUser = UserFactory::new()->create();

        ThreadParticipantFactory::new()->create(
            [
                'thread_id' => $threadOne->id,
                'user_id' => $user->id,
                'last_read_at' => null,
            ]
        );

        ThreadParticipantFactory::new()->create(
            [
                'thread_id' => $threadTwo->id,
                'user_id' => $user->id,
                'last_read_at' => Carbon::now(),
            ]
        );

        ThreadParticipantFactory::new()->create(
            [
                'thread_id' => $threadThree->id,
                'user_id' => $user->id,
                'last_read_at' => $timeNow,
            ]
        );

        ThreadParticipantFactory::new()->create(
            [
                'thread_id' => $threadFour->id,
                'user_id' => $irrelevantUser->id,
                'last_read_at' => Carbon::now()->addDay(),
            ]
        );

        ThreadParticipantFactory::new()->create(
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
