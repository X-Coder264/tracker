<?php

declare(strict_types=1);

namespace App\Services\Announce\Contracts;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer;
use App\Presenters\Announce\Snatch;
use App\Presenters\Announce\Torrent;
use App\Presenters\Announce\User;

interface EventAnnounceHandlerInterface
{
    public function handle(
        AnnounceRequest $request,
        User $user,
        Torrent $torrent,
        ?Peer $peer,
        ?Snatch $snatch,
        int $uploadedInThisAnnounceCycle,
        int $downloadedInThisAnnounceCycle
    ): string;
}
