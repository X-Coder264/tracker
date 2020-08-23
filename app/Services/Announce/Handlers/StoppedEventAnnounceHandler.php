<?php

declare(strict_types=1);

namespace App\Services\Announce\Handlers;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer;
use App\Presenters\Announce\Snatch;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;
use App\Services\Announce\Contracts\PeerRepositoryInterface;
use App\Services\Announce\Contracts\SnatchRepositoryInterface;
use App\Services\Announce\Contracts\TorrentRepositoryInterface;
use App\Services\Announce\Contracts\UserRepositoryInterface;
use App\Services\Announce\SuccessResponseFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;

final class StoppedEventAnnounceHandler extends AbstractHandler
{
    private ConnectionInterface $connection;
    private Repository $cache;

    public function __construct(
        UserRepositoryInterface $userRepository,
        TorrentRepositoryInterface $torrentRepository,
        PeerRepositoryInterface $peerRepository,
        SnatchRepositoryInterface $snatchRepository,
        SuccessResponseFactory $successResponseFactory,
        ConnectionInterface $connection,
        Repository $cache
    ) {
        parent::__construct(
            $userRepository,
            $torrentRepository,
            $peerRepository,
            $snatchRepository,
            $successResponseFactory
        );

        $this->connection = $connection;
        $this->cache = $cache;
    }

    public function handle(
        AnnounceRequest $request,
        User $user,
        Torrent $torrent,
        ?Peer $peer,
        ?Snatch $snatch,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): string {
        $this->connection->table('peers')->where('id', '=', $peer->getId())->delete();

        $this->cache->forget('user.' . $user->getId() . '.peers');

        if ($peer->isSeeder()) {
            $torrent = $this->adjustTorrentPeers($torrent, -1, 0);
        } else {
            $torrent = $this->adjustTorrentPeers($torrent, 0, -1);
        }

        $this->updateSnatchIfItExists($snatch, $peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);

        return $this->announceSuccessResponse($request, $user, $torrent, $peer, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
    }
}
