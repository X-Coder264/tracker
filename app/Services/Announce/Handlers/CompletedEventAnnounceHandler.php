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

final class CompletedEventAnnounceHandler extends AbstractHandler
{
    private Repository $cache;

    public function __construct(
        UserRepositoryInterface $userRepository,
        TorrentRepositoryInterface $torrentRepository,
        PeerRepositoryInterface $peerRepository,
        SnatchRepositoryInterface $snatchRepository,
        SuccessResponseFactory $successResponseFactory,
        Repository $cache
    ) {
        parent::__construct(
            $userRepository,
            $torrentRepository,
            $peerRepository,
            $snatchRepository,
            $successResponseFactory
        );

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
        $this->updateSnatchIfItExists($snatch, $peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
        $peer = $this->updatePeerIfItExists($peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
        $this->cache->forget('user.' . $user->getId() . '.peers');
        $torrent = $this->adjustTorrentPeers($torrent, 1, -1);

        return $this->announceSuccessResponse($request, $user, $torrent, $peer, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
    }
}
