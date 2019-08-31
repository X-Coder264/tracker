<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserTorrents;

use App\Models\User;
use Illuminate\Http\Response;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ShowSeedingTorrentsController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(UserRepository $userRepository, Translator $translator, ResponseFactory $responseFactory)
    {
        $this->userRepository = $userRepository;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(User $user): Response
    {
        $peers = $this->userRepository->getSeedingTorrentPeers($user->id, $user->torrents_per_page);

        $title = $this->translator->trans('messages.common.currently-seeding');

        $noTorrentsMessage = $this->translator->trans('messages.common.no-torrents-on-seed');

        return $this->responseFactory->view(
            'user-torrents.show-peers',
            compact('peers', 'title', 'user', 'noTorrentsMessage')
        );
    }
}
