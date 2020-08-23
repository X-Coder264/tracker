<?php

declare(strict_types=1);

namespace App\Services\Announce\Handlers;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer;
use App\Presenters\Announce\Snatch;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;

final class RegularUpdateEventAnnounceHandler extends AbstractHandler
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
            $this->updateSnatchIfItExists($snatch, $peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);

            if ($peer->isSeeder()) {
                $torrent = $this->adjustTorrentPeers($torrent, 1, 0);
            } else {
                $torrent = $this->adjustTorrentPeers($torrent, 0, 1);
            }
        } else {
            $this->updateSnatchIfItExists($snatch, $peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
            $peer = $this->updatePeerIfItExists($peer, $request, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
        }

        return $this->announceSuccessResponse($request, $user, $torrent, $peer, $uploadedInThisAnnounceCycle, $downloadedInThisAnnounceCycle);
    }
}
