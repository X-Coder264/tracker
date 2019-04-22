<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Enumerations\Cache;
use App\Presenters\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class CachedUserRepository implements UserRepositoryInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var CacheRepository
     */
    private $cache;

    public function __construct(
        UserRepository $userRepository,
        CacheRepository $cache
    )
    {
        $this->userRepository = $userRepository;
        $this->cache = $cache;
    }

    public function getUserByPassKey(string $passkey): ?User
    {
        return $this->cache->remember(sprintf('user.%s', $passkey), Cache::ONE_DAY, function () use ($passkey) {
            return $this->userRepository->getUserByPassKey($passkey);
        });
    }

    public function updateUserStatistics(User $user): void
    {
        $this->userRepository->updateUserStatistics($user);

        // cache user data to cache
        $this->cache->put(sprintf('user.%s', $user->getPasskey()), $user, Cache::ONE_DAY);
    }
}
