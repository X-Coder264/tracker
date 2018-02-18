<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Http\Models\Peer;
use Illuminate\Console\Command;

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

    public function handle(): void
    {
        $obsoletePeerIds = Peer::select('id')->where('updated_at', '<', Carbon::now()->subMinutes(45))->get()->pluck('id');
        Peer::destroy($obsoletePeerIds);
    }
}
