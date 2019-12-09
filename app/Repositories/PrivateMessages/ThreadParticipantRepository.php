<?php

declare(strict_types=1);

namespace App\Repositories\PrivateMessages;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class ThreadParticipantRepository implements ThreadParticipantRepositoryInterface
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getUnreadThreadsForUser(int $userId): Collection
    {
        return $this->connection->table('thread_participants')
            ->join('threads', 'thread_participants.thread_id', '=', 'threads.id')
            ->where('thread_participants.user_id', '=', $userId)
            ->where(function (Builder $query) {
                $query->whereNull('thread_participants.last_read_at')
                    ->orWhereColumn('thread_participants.last_read_at', '<', 'threads.updated_at');
            })
            ->pluck('thread_participants.thread_id');
    }
}
