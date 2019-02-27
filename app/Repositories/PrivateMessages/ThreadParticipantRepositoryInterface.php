<?php

declare(strict_types=1);

namespace App\Repositories\PrivateMessages;

use Illuminate\Support\Collection;

interface ThreadParticipantRepositoryInterface
{
    public function getUnreadThreadsForUser(int $userId): Collection;
}
