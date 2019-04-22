<?php

declare(strict_types=1);

namespace App\Services\Announce\Scrape;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use stdClass;

final class Manager
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function scrape(string $infoHash, string ...$infoHashes): array
    {
        array_unshift($infoHashes, $infoHash);

        $response = [];

        /** @var Collection $torrents */
        $torrents = $this->connection
            ->table('torrents')
            ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
            ->whereIn(
                'info_hash',
                array_map(
                    function ($val){
                        return bin2hex($val);
                    },
                    $infoHashes
                )
            )
            ->select(['torrents.id', 'seeders', 'leechers', 'info_hash'])
            ->get();

        $torrents->each(function(stdClass $torrent) use (&$response){
            $snatchesCount = $this->connection
                ->table('snatches')
                ->where('torrent_id', '=', $torrent->id)
                ->where('left', '=', 0)
                ->count();

            $response[hex2bin($torrent->info_hash)] = [
                'complete' => (int) $torrent->seeders,
                'incomplete' => (int) $torrent->leechers,
                'downloaded' => $snatchesCount,
            ];
        });

        return $response;
    }
}
