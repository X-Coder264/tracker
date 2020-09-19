<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories\PrivateMessages;

use App\Repositories\PrivateMessages\CachedThreadParticipantRepository;
use Database\Factories\ThreadFactory;
use Database\Factories\ThreadParticipantFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CachedThreadParticipantRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetUnreadThreadsForUser(): void
    {
        $user = UserFactory::new()->create();

        $thread = ThreadFactory::new()->create();

        ThreadParticipantFactory::new()->create(
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
