<?php

declare(strict_types=1);

namespace App\Services\Announce\Handlers;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer;
use App\Presenters\Announce\Snatch;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;
use Carbon\CarbonImmutable;

final class StartedEventAnnounceHandler extends AbstractHandler
{
    public function handle(
        AnnounceRequest $request,
        User $user,
        Torrent $torrent,
        ?Peer $peer,
        ?Snatch $snatch,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): string {
        if (null === $peer) {
            $peer = $this->insertPeer($request, $user, $torrent, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);

            if ($peer->isSeeder()) {
                $torrent = $this->adjustTorrentPeers($torrent, 1, 0);
            } else {
                $torrent = $this->adjustTorrentPeers($torrent, 0, 1);

                if (null === $snatch) {
                    $this->insertSnatch($request, $user, $torrent, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
                } else {
                    $this->updateSnatchIfItExists($snatch, $peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
                }
            }
        } else {
            $this->updateSnatchIfItExists($snatch, $peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
            $peer = $this->updatePeerIfItExists($peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
        }

        return $this->announceSuccessResponse($request, $user, $torrent, $peer, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
    }

    private function insertSnatch(
        AnnounceRequest $request,
        User $user,
        Torrent $torrent,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): void {
        $now = CarbonImmutable::now();

        $this->snatch = $this->snatchRepository->insertSnatch(
            new Snatch(
                null,
                $uploadedInThisAnnounceCycle,
                $downloadedInThisAnnounceCycle,
                $request->getLeft(),
                0,
                0,
                1,
                $now,
                $now,
                null,
                $request->getUserAgent()
            ),
            $torrent,
            $user
        );
    }
}
