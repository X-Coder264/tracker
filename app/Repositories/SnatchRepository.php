<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Presenters\Announce\Snatch as AnnounceSnatchModel;
use App\Presenters\Announce\Torrent as AnnounceTorrentModel;
use App\Presenters\Announce\User as AnnounceUserModel;
use App\Services\Announce\Contracts\SnatchRepositoryInterface as AnnounceSnatchRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;

final class SnatchRepository implements AnnounceSnatchRepositoryInterface
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function insertSnatch(AnnounceSnatchModel $snatch, AnnounceTorrentModel $torrent, AnnounceUserModel $user): AnnounceSnatchModel
    {
        $snatchId = $this->connection->table('snatches')->insertGetId(
            [
                'torrent_id'      => $torrent->getId(),
                'user_id'         => $user->getId(),
                'uploaded'        => $snatch->getUploaded(),
                'downloaded'      => $snatch->getDownloaded(),
                'left'            => $snatch->getLeft(),
                'times_announced' => $snatch->getTimesAnnounced(),
                'user_agent'      => $snatch->getUserAgent(),
                'created_at'      => $snatch->getCreatedAt(),
                'updated_at'      => $snatch->getUpdatedAt(),
            ]
        );

        return $snatch->withId($snatchId);
    }

    public function updateSnatch(AnnounceSnatchModel $snatch): void
    {
        $this->connection->table('snatches')
            ->where('id', '=', $snatch->getId())
            ->update(
                [
                    'uploaded'        => $snatch->getUploaded(),
                    'downloaded'      => $snatch->getDownloaded(),
                    'left'            => $snatch->getLeft(),
                    'seed_time'       => $snatch->getSeedTime(),
                    'leech_time'      => $snatch->getLeechTime(),
                    'times_announced' => $snatch->getTimesAnnounced(),
                    'finished_at'     => $snatch->getFinishedAt(),
                    'user_agent'      => $snatch->getUserAgent(),
                    'updated_at'      => $snatch->getUpdatedAt(),
                ]
            );
    }

    public function findTorrentSnatchOfUser(AnnounceTorrentModel $torrent, AnnounceUserModel $user): ?AnnounceSnatchModel
    {
        $snatch = $this->connection->table('snatches')
            ->where('torrent_id', '=', $torrent->getId())
            ->where('user_id', '=', $user->getId())
            ->first();

        if (null === $snatch) {
            return null;
        }

        return new AnnounceSnatchModel(
            $snatch->id,
            $snatch->uploaded,
            $snatch->downloaded,
            $snatch->left,
            $snatch->seed_time,
            $snatch->leech_time,
            $snatch->times_announced,
            $snatch->created_at ? new CarbonImmutable($snatch->created_at) : null,
            $snatch->updated_at ? new CarbonImmutable($snatch->updated_at) : null,
            $snatch->finished_at ? new CarbonImmutable($snatch->finished_at) : null,
            $snatch->user_agent,
        );
    }
}
