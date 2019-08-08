<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\PeerRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;

final class DeletePeers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peers:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all obsolete peers';

    public function handle(PeerRepository $peerRepository, ConnectionInterface $connection, Repository $cache): void
    {
        $count = 0;
        foreach ($peerRepository->getObsoletePeersQuery()->cursor() as $obsoletePeer) {
            $connection->table('peers')->where('id', '=', $obsoletePeer->id)->delete();
            $count++;
            if ($obsoletePeer->seeder) {
                $connection->table('torrents')->where('id', '=', $obsoletePeer->torrent_id)->decrement('seeders');
            } else {
                $connection->table('torrents')->where('id', '=', $obsoletePeer->torrent_id)->decrement('leechers');
            }
            $cache->forget('torrent.' . $obsoletePeer->torrent_id);
            $cache->forget('user.' . $obsoletePeer->user_id . '.peers');
        }

        $this->info(sprintf('Deleted obsolete peers: %d', $count));
    }
}
