<?php

declare(strict_types=1);

namespace App\Repositories\PrivateMessages;

use App\Enumerations\Cache;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Cache\Repository;

class CachedThreadParticipantRepository implements ThreadParticipantRepositoryInterface
{
    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var ThreadParticipantRepositoryInterface
     */
    private $threadParticipantRepository;

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
