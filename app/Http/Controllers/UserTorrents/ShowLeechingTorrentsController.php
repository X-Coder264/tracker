<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserTorrents;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;

final class ShowLeechingTorrentsController
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
        $peers = $this->userRepository->getLeechingTorrentPeers($user->id, $user->torrents_per_page);

        $title = $this->translator->get('messages.common.currently-leeching');

        $noTorrentsMessage = $this->translator->get('messages.common.no-torrents-on-leech');

        return $this->responseFactory->view(
            'user-torrents.show-peers',
            compact('peers', 'title', 'user', 'noTorrentsMessage')
        );
    }
}
