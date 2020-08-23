<?php

declare(strict_types=1);

namespace App\Services\Announce\Handlers;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer;
use App\Presenters\Announce\Response\Peer as ResponsePeer;
use App\Presenters\Announce\Response\PeersCount;
use App\Presenters\Announce\Snatch;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;
use App\Services\Announce\Contracts\EventAnnounceHandlerInterface;
use App\Services\Announce\Contracts\PeerRepositoryInterface;
use App\Services\Announce\Contracts\SnatchRepositoryInterface;
use App\Services\Announce\Contracts\TorrentRepositoryInterface;
use App\Services\Announce\Contracts\UserRepositoryInterface;
use App\Services\Announce\SuccessResponseFactory;
use Carbon\CarbonImmutable;

abstract class AbstractHandler implements EventAnnounceHandlerInterface
{
    protected UserRepositoryInterface $userRepository;
    protected TorrentRepositoryInterface $torrentRepository;
    protected PeerRepositoryInterface $peerRepository;
    protected SnatchRepositoryInterface $snatchRepository;
    protected SuccessResponseFactory $successResponseFactory;

    public function __construct(
        UserRepositoryInterface $userRepository,
        TorrentRepositoryInterface $torrentRepository,
        PeerRepositoryInterface $peerRepository,
        SnatchRepositoryInterface $snatchRepository,
        SuccessResponseFactory $successResponseFactory
    ) {
        $this->userRepository = $userRepository;
        $this->torrentRepository = $torrentRepository;
        $this->peerRepository = $peerRepository;
        $this->snatchRepository = $snatchRepository;
        $this->successResponseFactory = $successResponseFactory;
    }

    protected function adjustTorrentPeers(Torrent $torrent, int $seeder, int $leecher): Torrent
    {
        $torrentWithUpdatedInfo = Torrent::createFromSelfWithUpdatedSeedersAndLeechersCount(
            $torrent,
            $torrent->getSeedersCount() + $seeder,
            $torrent->getLeechersCount() + $leecher
        );

        $this->torrentRepository->updateTorrentSeederAndLeechersCount($torrentWithUpdatedInfo);

        return $torrentWithUpdatedInfo;
    }

    protected function insertPeer(
        AnnounceRequest $request,
        User $user,
        Torrent $torrent,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): Peer {
        $now = CarbonImmutable::now();

        return $this->peerRepository->insertPeer(
            new Peer(
                null,
                $uploadedInThisAnnounceCycle,
                $downloadedInThisAnnounceCycle,
                $request->getLeft(),
                bin2hex($request->getPeerId()),
                $request->getClientIp()->getIp(),
                $request->getClientIp()->isIPv6(),
                $request->getPort(),
                $torrent->getVersion(),
                $request->getUserAgent(),
                $request->getKey(),
                $now,
                $now
            ),
            $torrent,
            $user,
            $request
        );
    }

    protected function updatePeerIfItExists(
        ?Peer $peer,
        AnnounceRequest $request,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): ?Peer {
        if (null !== $peer) {
            $now = CarbonImmutable::now();

            $peer = new Peer(
                $peer->getId(),
                $peer->getUploaded() + $uploadedInThisAnnounceCycle,
                $peer->getDownloaded() + $downloadedInThisAnnounceCycle,
                $request->getLeft(),
                $peer->getPeerId(),
                $peer->getIpAddress(),
                $peer->isIPv6(),
                $peer->getPort(),
                $peer->getVersion(),
                $request->getUserAgent(),
                $request->getKey(),
                $peer->getCreatedAt(),
                $now
            );

            $this->peerRepository->updatePeer($peer, $request);
        }

        return $peer;
    }

    protected function updateSnatchIfItExists(
        ?Snatch $snatch,
        Peer $peer,
        AnnounceRequest $request,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): void {
        if (null !== $snatch) {
            $now = CarbonImmutable::now();

            $finishedAt = $snatch->getFinishedAt();
            if (0 === $request->getLeft() && null === $finishedAt) {
                $finishedAt = $now;
            }

            $leechTime = 0;
            $seedTime = 0;

            if (0 !== $request->getLeft() || (0 === $request->getLeft() && $request->getEvent()->isCompleted())) {
                $leechTime = $now->diffInSeconds($peer->getUpdatedAt());
            } else {
                $seedTime = $now->diffInSeconds($peer->getUpdatedAt());
            }

            $snatch = new Snatch(
                $snatch->getId(),
                $snatch->getUploaded() + $uploadedInThisAnnounceCycle,
                $snatch->getDownloaded() + $downloadedInThisAnnounceCycle,
                $request->getLeft(),
                $snatch->getSeedTime() + $seedTime,
                $snatch->getLeechTime() + $leechTime,
                $snatch->getTimesAnnounced() + 1,
                $snatch->getCreatedAt(),
                $now,
                $finishedAt,
                $request->getUserAgent()
            );

            $this->snatchRepository->updateSnatch($snatch);
        }
    }

    protected function announceSuccessResponse(
        AnnounceRequest $request,
        User $user,
        Torrent $torrent,
        Peer $peer,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): string {
        $this->updateUserUploadedAndDownloadedStats(
            $request,
            $user,
            $uploadedInThisAnnounceCycle,
            $downloadedInThisAnnounceCycle
        );

        $peersFromStorage = $this->peerRepository->getPeersForTorrent(
            $torrent,
            $user,
            ! $peer->isSeeder(),
            $request->getNumberOfWantedPeers()
        );

        $peers = [];

        foreach ($peersFromStorage as $peerFromStorage) {
            $peers[] = new ResponsePeer(
                $peerFromStorage->getIpAddress(),
                $peerFromStorage->isIPv6(),
                $peerFromStorage->getPort(),
                $peerFromStorage->getPeerId()
            );
        }

        $peersCount = $this->getPeersCount($request, $torrent, $peer);

        return $this->getAnnounceResponse($request, $peers, $peersCount);
    }

    /**
     * @param ResponsePeer[] $peers
     */
    protected function getAnnounceResponse(AnnounceRequest $request, array $peers, PeersCount $peersCount): string
    {
        if ($request->isCompactResponseExpected()) {
            return $this->successResponseFactory->getCompactResponse($peers, $peersCount);
        }

        return $this->successResponseFactory->getNonCompactResponse($peers, $peersCount);
    }

    protected function updateUserUploadedAndDownloadedStats(
        AnnounceRequest $request,
        User $user,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): void {
        $uploaded = $user->getUploaded() + $uploadedInThisAnnounceCycle;
        $downloaded = $user->getDownloaded() + $downloadedInThisAnnounceCycle;

        $this->userRepository->updateUserUploadedAndDownloadedStats(
            $request->getPasskey(),
            User::createFromSelfWithUpdatedUploadedAndDownloadedStats($user, $uploaded, $downloaded)
        );
    }

    private function getPeersCount(AnnounceRequest $request, Torrent $torrent, Peer $peer): PeersCount
    {
        $event = $request->getEvent();

        $seedersCount = $torrent->getSeedersCount();
        $leechersCount = $torrent->getLeechersCount();
        // We don't want to include the current user/peer in the returned seeder/leecher count.
        if (! $event->isStopped()) {
            if ($peer->isSeeder()) {
                $seedersCount--;
            } else {
                $leechersCount--;
            }
        }

        return new PeersCount($seedersCount, $leechersCount);
    }
}
