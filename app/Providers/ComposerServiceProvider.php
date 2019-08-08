<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\ViewComposers\TimezoneComposer;
use App\Http\ViewComposers\UserStatsComposer;
use Illuminate\Contracts\View\Factory as ViewFactory;
use App\Http\ViewComposers\UnreadPrivateMessagesComposer;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $viewsThatNeedTimezoneInfo = [
        'torrents.index', 'torrents.show', 'users.show', 'snatches.show',
        'partials.torrent', 'partials.peer', 'user-snatches.show', 'private-messages.thread-index',
        'private-messages.thread-show', 'invites.create',
    ];

    public function boot(ViewFactory $viewFactory): void
    {
        $viewFactory->composer(
            ['partials.user-statistics'],
            UserStatsComposer::class
        );

        $viewFactory->composer(
            ['partials.user-statistics'],
            UnreadPrivateMessagesComposer::class
        );

        $viewFactory->composer(
            $this->viewsThatNeedTimezoneInfo,
            TimezoneComposer::class
        );
    }
}
