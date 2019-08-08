<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invite;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class DeleteExpiredInvitesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invites:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all expired invites';

    public function handle(): void
    {
        Invite::where('expires_at', '<=', CarbonImmutable::now())->delete();
    }
}
