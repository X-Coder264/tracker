<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserTorrents;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;

final class ShowUploadedTorrentsController
{
    private Guard $guard;
    private Translator $translator;
    private UserRepository $userRepository;
    private ResponseFactory $responseFactory;

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

    public function __invoke(User $user): Response
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->guard->user();

        if ($user->is($loggedInUser)) {
            $title = $this->translator->get('messages.torrent.current-user.page_title');
        } else {
            $title = $this->translator->get('messages.torrent.user.page_title');
        }

        $torrents = $this->userRepository->getUploadedTorrents($user->id, $user->torrents_per_page);

        return $this->responseFactory->view('user-torrents.show', compact('torrents', 'title', 'user'));
    }
}
