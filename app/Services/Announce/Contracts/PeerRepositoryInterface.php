<?php

declare(strict_types=1);

namespace App\Services\Announce\Contracts;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;

interface PeerRepositoryInterface
{
    public function insertPeer(Peer $peer, Torrent $torrent, User $user, AnnounceRequest $request): Peer;

    public function updatePeer(Peer $peer, AnnounceRequest $request): void;

    public function findPeerByPeerIdAndKey(string $peerId, ?string $key, Torrent $torrent, User $user): ?Peer;

    /**
     * @return Peer[]
     */
    public function getPeersForTorrent(Torrent $torrent, User $user, bool $includeSeeders, int $numberOfWantedPeers): iterable;
}
