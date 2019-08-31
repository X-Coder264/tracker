<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use App\Enumerations\Cache;
use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use App\Services\StatisticsManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;

final class HomeController
{
    /**
     * @var StatisticsManager
     */
    private $statisticsManager;

    /**
     * @var SizeFormatter
     */
    private $sizeFormatter;

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        StatisticsManager $statisticsManager,
        SizeFormatter $sizeFormatter,
        Repository $cache,
        ResponseFactory $responseFactory
    ) {
        $this->statisticsManager = $statisticsManager;
        $this->sizeFormatter = $sizeFormatter;
        $this->responseFactory = $responseFactory;
        $this->cache = $cache;
    }

    public function __invoke(): Response
    {
        $news = $this->cache->remember('news', Cache::ONE_DAY, function (): ?News {
            return News::with('author')->orderByDesc('id')->first();
        });

        return $this->responseFactory->view(
            'home.index',
            [
                'usersCount' => $this->statisticsManager->getUsersCount(),
                'bannedUsersCount' => $this->statisticsManager->getBannedUsersCount(),
                'peersCount' => $this->statisticsManager->getPeersCount(),
                'seedersCount' => $this->statisticsManager->getSeedersCount(),
                'leechersCount' => $this->statisticsManager->getLeechersCount(),
                'torrentsCount' => $this->statisticsManager->getTorrentsCount(),
                'deadTorrentsCount' => $this->statisticsManager->getDeadTorrentsCount(),
                'totalTorrentSize' => $this->sizeFormatter->getFormattedSize($this->statisticsManager->getTotalTorrentSize()),
                'news' => $news,
            ]
        );
    }
}
