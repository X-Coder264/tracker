<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserTorrents;

use App\Models\User;
use Illuminate\Http\Response;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ShowUploadedTorrentsController
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

    public function __invoke(User $user): Response
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
}
