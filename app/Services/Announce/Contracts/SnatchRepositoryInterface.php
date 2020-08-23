<?php

declare(strict_types=1);

namespace App\Services\Announce\Contracts;

use App\Presenters\Announce\Snatch;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;

interface SnatchRepositoryInterface
{
    public function insertSnatch(Snatch $snatch, Torrent $torrent, User $user): Snatch;

    public function updateSnatch(Snatch $snatch): void;

    public function findTorrentSnatchOfUser(Torrent $torrent, User $user): ?Snatch;
}
