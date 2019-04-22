<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use App\Repositories\User\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

class UserTorrentsController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Guard $guard,
        Translator $translator,
        UserRepository $userRepository,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->responseFactory = $responseFactory;
    }

    public function showUploadedTorrents(User $user): Response
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->guard->user();
        if ($user->is($loggedInUser)) {
            $title = $this->translator->trans('messages.torrent.current-user.page_title');
        } else {
            $title = $this->translator->trans('messages.torrent.user.page_title');
        }

        $torrents = $this->userRepository->getUploadedTorrents($user->id, $user->torrents_per_page);

        return $this->responseFactory->view('user-torrents.show', compact('torrents', 'title', 'user'));
    }

    public function showSeedingTorrents(User $user): Response
    {
        $peers = $this->userRepository->getSeedingTorrentPeers($user->id, $user->torrents_per_page);

        $title = $this->translator->trans('messages.common.currently-seeding');

        $noTorrentsMessage = $this->translator->trans('messages.common.no-torrents-on-seed');

        return $this->responseFactory->view(
            'user-torrents.show-peers',
            compact('peers', 'title', 'user', 'noTorrentsMessage')
        );
    }

    public function showLeechingTorrents(User $user): Response
    {
        $peers = $this->userRepository->getLeechingTorrentPeers($user->id, $user->torrents_per_page);

        $title = $this->translator->trans('messages.common.currently-leeching');

        $noTorrentsMessage = $this->translator->trans('messages.common.no-torrents-on-leech');

        return $this->responseFactory->view(
            'user-torrents.show-peers',
            compact('peers', 'title', 'user', 'noTorrentsMessage')
        );
    }
}
