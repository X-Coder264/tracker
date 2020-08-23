<?php

declare(strict_types=1);

namespace App\Services\Announce\Contracts;

use App\Presenters\Announce\User;
use App\Presenters\Announce\User as AnnounceUserModel;

interface UserRepositoryInterface
{
    public function getUserFromPasskey(string $passkey): ?User;

    public function updateUserUploadedAndDownloadedStats(string $passkey, AnnounceUserModel $user): void;
}
