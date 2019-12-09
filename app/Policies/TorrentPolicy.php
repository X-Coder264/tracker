<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Torrent;
use App\Models\User;

class TorrentPolicy
{
    public function update(User $user, Torrent $torrent): bool
    {
        return $torrent->uploader->is($user);
    }
}
