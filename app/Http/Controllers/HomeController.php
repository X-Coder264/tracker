<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use App\Services\StatisticsManager;
use Illuminate\Contracts\Routing\ResponseFactory;

class HomeController
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
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        StatisticsManager $statisticsManager,
        SizeFormatter $sizeFormatter,
        ResponseFactory $responseFactory
    ) {
        $this->statisticsManager = $statisticsManager;
        $this->sizeFormatter = $sizeFormatter;
        $this->responseFactory = $responseFactory;
    }

    public function index(): Response
    {
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
            ]
        );
    }
}
