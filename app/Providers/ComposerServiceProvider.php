<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\ViewComposers\TimezoneComposer;
use App\Http\ViewComposers\UserStatsComposer;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $viewsThatNeedTimezoneInfo = ['torrents.index', 'torrents.show', 'users.show', 'snatches.show'];

    public function boot(ViewFactory $viewFactory)
    {
        $viewFactory->composer(
            ['layouts.app'],
            UserStatsComposer::class
        );

        $viewFactory->composer(
            $this->viewsThatNeedTimezoneInfo,
            TimezoneComposer::class
        );
    }
}
