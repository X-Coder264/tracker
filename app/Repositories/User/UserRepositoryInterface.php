<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Presenters\User;

interface UserRepositoryInterface
{
    public function getUserByPassKey(string $passkey): ?User;

    public function updateUserStatistics(User $user): void;
}
