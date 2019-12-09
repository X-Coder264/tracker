<?php

declare(strict_types=1);

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class PeerRepository
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var Repository
     */
    private $config;

    public function __construct(ConnectionInterface $connection, Repository $config)
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    public function getObsoletePeersQuery(): Builder
    {
        return $this->connection
            ->table('peers')
            ->where('updated_at', '<', Carbon::now()->subMinutes($this->config->get('tracker.announce_interval') + 10))
            ->select(['id', 'seeder', 'torrent_id', 'user_id']);
    }
}
