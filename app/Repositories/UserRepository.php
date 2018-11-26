<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\ConnectionInterface;

class UserRepository
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getTotalSeedingSize(int $userId): int
    {
        return (int) $this->connection->table('torrents')
            ->join('peers', 'torrents.id', '=', 'peers.torrent_id')
            ->where('peers.user_id', '=', $userId)
            ->where('peers.seeder', '=', true)
            ->sum('size');
    }
}
