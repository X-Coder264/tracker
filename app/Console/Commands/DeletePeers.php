<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;

class DeletePeers extends Command
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

    public function handle(ConnectionInterface $connection, Repository $config): void
    {
        $obsoletePeerIds = $connection
            ->table('peers')
            ->where('updated_at', '<', Carbon::now()->subMinutes($config->get('tracker.announce_interval') + 5))
            ->select('id')
            ->get()
            ->pluck('id');

        if ($obsoletePeerIds->isNotEmpty()) {
            $connection->table('peers')->whereIn('id', $obsoletePeerIds)->delete();
        }

        $this->info(sprintf('%s obsolete peers were deleted.', $obsoletePeerIds->count()));
    }
}
