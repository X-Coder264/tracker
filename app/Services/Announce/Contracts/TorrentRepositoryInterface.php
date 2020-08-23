<?php

declare(strict_types=1);

namespace App\Services\Announce\Contracts;

use App\Presenters\Announce\Torrent;

interface TorrentRepositoryInterface
{
    public function getTorrentByInfoHash(string $infoHash): ?Torrent;

    public function updateTorrentSeederAndLeechersCount(Torrent $torrent): void;
}
