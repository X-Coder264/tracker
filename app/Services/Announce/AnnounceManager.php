<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Presenters\Announce\AnnounceEvent;
use App\Presenters\Announce\AnnounceRequest;
use App\Services\Announce\Contracts\EventAnnounceHandlerInterface;
use App\Services\Announce\Contracts\PeerRepositoryInterface;
use App\Services\Announce\Contracts\SnatchRepositoryInterface;
use App\Services\Announce\Contracts\TorrentRepositoryInterface;
use App\Services\Announce\Contracts\UserRepositoryInterface;
use App\Services\Announce\Handlers\CompletedEventAnnounceHandler;
use App\Services\Announce\Handlers\RegularUpdateEventAnnounceHandler;
use App\Services\Announce\Handlers\StartedEventAnnounceHandler;
use App\Services\Announce\Handlers\StoppedEventAnnounceHandler;
use Illuminate\Contracts\Translation\Translator;

/**
 * Note: For performance reasons the query builder is used instead of Eloquent.
 */
class AnnounceManager
{
    private UserRepositoryInterface $userRepository;
    private TorrentRepositoryInterface $torrentRepository;
    private PeerRepositoryInterface $peerRepository;
    private SnatchRepositoryInterface $snatchRepository;
    private Translator $translator;
    private StartedEventAnnounceHandler $startedEventAnnounceHandler;
    private StoppedEventAnnounceHandler $stoppedEventAnnounceHandler;
    private CompletedEventAnnounceHandler $completedEventAnnounceHandler;
    private RegularUpdateEventAnnounceHandler $regularUpdateEventAnnounceHandler;
    private ErrorResponseFactory $errorResponseFactory;

    public function __construct(
        UserRepositoryInterface $userRepository,
        TorrentRepositoryInterface $torrentRepository,
        PeerRepositoryInterface $peerRepository,
        SnatchRepositoryInterface $snatchRepository,
        Translator $translator,
        StartedEventAnnounceHandler $startedEventAnnounceHandler,
        StoppedEventAnnounceHandler $stoppedEventAnnounceHandler,
        CompletedEventAnnounceHandler $completedEventAnnounceHandler,
        RegularUpdateEventAnnounceHandler $regularUpdateEventAnnounceHandler,
        ErrorResponseFactory $errorResponseFactory
    ) {
        $this->userRepository = $userRepository;
        $this->torrentRepository = $torrentRepository;
        $this->peerRepository = $peerRepository;
        $this->snatchRepository = $snatchRepository;
        $this->translator = $translator;
        $this->startedEventAnnounceHandler = $startedEventAnnounceHandler;
        $this->stoppedEventAnnounceHandler = $stoppedEventAnnounceHandler;
        $this->completedEventAnnounceHandler = $completedEventAnnounceHandler;
        $this->regularUpdateEventAnnounceHandler = $regularUpdateEventAnnounceHandler;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    public function announce(AnnounceRequest $request): string
    {
        $user = $this->userRepository->getUserFromPasskey($request->getPasskey());

        if (null === $user) {
            return $this->announceErrorResponse($this->translator->get('messages.announce.invalid_passkey'), true);
        }

        if ($user->isBanned()) {
            return $this->announceErrorResponse($this->translator->get('messages.announce.banned_user'), true);
        }

        $torrent = $this->torrentRepository->getTorrentByInfoHash(bin2hex($request->getInfoHash()));

        if (null === $torrent) {
            return $this->announceErrorResponse($this->translator->get('messages.announce.invalid_info_hash'));
        }

        $peer = $this->peerRepository->findPeerByPeerIdAndKey(
            bin2hex($request->getPeerId()),
            $request->getKey(),
            $torrent,
            $user
        );

        $event = $request->getEvent();

        if (null === $peer && ($event->isCompleted() || $event->isStopped())) {
            return $this->announceErrorResponse($this->translator->get('messages.announce.invalid_peer_id'));
        }

        $downloaded = $request->getDownloaded();
        $uploaded = $request->getUploaded();

        if (null === $peer) {
            $downloadedInThisAnnounceCycle = $downloaded;
            $uploadedInThisAnnounceCycle = $uploaded;
        } else {
            $downloadedInThisAnnounceCycle = max(0, $downloaded - $peer->getDownloaded());
            $uploadedInThisAnnounceCycle = max(0, $uploaded - $peer->getUploaded());
        }

        $snatch = $this->snatchRepository->findTorrentSnatchOfUser($torrent, $user);

        return $this->getHandlerForEvent($event, $request->getLeft())->handle(
            $request,
            $user,
            $torrent,
            $peer,
            $snatch,
            $uploadedInThisAnnounceCycle,
            $downloadedInThisAnnounceCycle
        );
    }

    private function getHandlerForEvent(AnnounceEvent $event, int $left): EventAnnounceHandlerInterface
    {
        if ($event->isStarted()) {
            return $this->startedEventAnnounceHandler;
        } elseif ($event->isStopped()) {
            return $this->stoppedEventAnnounceHandler;
        } elseif ($event->isCompleted() && 0 === $left) {
            return $this->completedEventAnnounceHandler;
        }

        return $this->regularUpdateEventAnnounceHandler;
    }

    /**
     * @param array|string $error
     */
    private function announceErrorResponse($error, bool $neverRetry = false): string
    {
        return $this->errorResponseFactory->create($error, $neverRetry);
    }
}
