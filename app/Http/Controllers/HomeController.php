<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Services\StatisticsManager;
use Illuminate\Contracts\Routing\ResponseFactory;

class HomeController
{
    public function index(StatisticsManager $statisticsManager, ResponseFactory $responseFactory): Response
    {
        return $responseFactory->view(
            'home.index',
            [
                'usersCount' => $statisticsManager->getUsersCount(),
                'bannedUsersCount' => $statisticsManager->getBannedUsersCount(),
                'peersCount' => $statisticsManager->getPeersCount(),
                'seedersCount' => $statisticsManager->getSeedersCount(),
                'leechersCount' => $statisticsManager->getLeechersCount(),
                'torrentsCount' => $statisticsManager->getTorrentsCount(),
                'deadTorrentsCount' => $statisticsManager->getDeadTorrentsCount(),
            ]
        );
    }
}
