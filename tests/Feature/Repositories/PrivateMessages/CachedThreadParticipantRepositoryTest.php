<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories\PrivateMessages;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Database\Connection;
use App\Models\PrivateMessages\Thread;
use Illuminate\Contracts\Cache\Repository;
use App\Models\PrivateMessages\ThreadParticipant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Repositories\PrivateMessages\CachedThreadParticipantRepository;

class CachedThreadParticipantRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetUnreadThreadsForUser(): void
    {
        $user = factory(User::class)->create();

        $thread = factory(Thread::class)->create();

        factory(ThreadParticipant::class)->create(
            [
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'last_read_at' => null,
            ]
        );

        $cache = $this->app->make(Repository::class);
        $connection = $this->app->make(Connection::class);

        $this->assertFalse($cache->has(sprintf('user.%d.unreadThreads', $user->id)));

        $repository = $this->app->make(CachedThreadParticipantRepository::class);

        $unreadThreads = $repository->getUnreadThreadsForUser($user->id);

        $this->assertTrue($unreadThreads->contains($thread->id));
        $this->assertTrue($cache->has(sprintf('user.%d.unreadThreads', $user->id)));
        $this->assertTrue($cache->get(sprintf('user.%d.unreadThreads', $user->id))->contains($thread->id));

        $beforeQueryLog = $connection->getQueryLog();

        $repository->getUnreadThreadsForUser($user->id);

        $afterQueryLog = $connection->getQueryLog();

        $this->assertSame(count($beforeQueryLog), count($afterQueryLog));
    }
}
