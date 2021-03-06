<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\SizeFormatter;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

final class ShowController
{
    private SizeFormatter $sizeFormatter;
    private UserRepository $userRepository;
    private ResponseFactory $responseFactory;

    public function __construct(SizeFormatter $sizeFormatter, UserRepository $userRepository, ResponseFactory $responseFactory)
    {
        $this->sizeFormatter = $sizeFormatter;
        $this->userRepository = $userRepository;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(User $user): Response
    {
        $totalSeedingSize = $this->sizeFormatter->getFormattedSize($this->userRepository->getTotalSeedingSize($user->id));

        $uploadedTorrentsCount = $this->userRepository->getUploadedTorrentsCount($user->id);
        $seedingTorrentPeersCount = $this->userRepository->getSeedingTorrentPeersCount($user->id);
        $leechingTorrentPeersCount = $this->userRepository->getLeechingTorrentPeersCount($user->id);
        $snatchesCount = $this->userRepository->getUserSnatchesCount($user->id);

        return $this->responseFactory->view(
            'users.show',
            compact(
                'user',
                'totalSeedingSize',
                'uploadedTorrentsCount',
                'seedingTorrentPeersCount',
                'leechingTorrentPeersCount',
                'snatchesCount'
            )
        );
    }
}
