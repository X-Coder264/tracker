<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\ViewComposers\TimezoneComposer;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $viewsThatNeedTimezoneInfo = ['torrents.index', 'torrents.show'];

    /**
     * @param ViewFactory $viewFactory
     */
    public function boot(ViewFactory $viewFactory)
    {
        $viewFactory->composer(
            $this->viewsThatNeedTimezoneInfo,
            TimezoneComposer::class
        );
    }
}
