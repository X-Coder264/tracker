<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

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

    /**
     * @param DatabaseManager $databaseManager
     */
    public function handle(DatabaseManager $databaseManager): void
    {
        $obsoletePeerIds = $databaseManager
            ->table('peers')
            ->where('updated_at', '<', Carbon::now()->subMinutes(45))
            ->select('id')
            ->get()
            ->pluck('id');

        if ($obsoletePeerIds->isNotEmpty()) {
            $databaseManager->table('peers')->whereIn('id', $obsoletePeerIds)->delete();
        }

        $this->info(sprintf('%s obsolete peers were deleted.', $obsoletePeerIds->count()));
    }
}
