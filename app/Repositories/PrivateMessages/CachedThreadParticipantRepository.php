<?php

declare(strict_types=1);

namespace App\Repositories\PrivateMessages;

use App\Enumerations\Cache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;

class CachedThreadParticipantRepository implements ThreadParticipantRepositoryInterface
{
    private Repository $cache;
    private ThreadParticipantRepositoryInterface $threadParticipantRepository;

    public function __construct(Repository $cache, ThreadParticipantRepositoryInterface $threadParticipantRepository)
    {
        $this->cache = $cache;
        $this->threadParticipantRepository = $threadParticipantRepository;
    }

    public function getUnreadThreadsForUser(int $userId): Collection
    {
        return $this->cache->remember(
            sprintf('user.%d.unreadThreads', $userId),
            Cache::THIRTY_MINUTES,
            function () use ($userId): Collection {
                return $this->threadParticipantRepository->getUnreadThreadsForUser($userId);
            }
        );
    }
}
