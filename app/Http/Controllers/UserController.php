<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Locale;
use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use Illuminate\Routing\Redirector;
use App\Repositories\User\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class UserController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var UserRepository
     */
    private $userRepository;

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
        Guard $guard,
        UserRepository $userRepository,
        SizeFormatter $sizeFormatter,
        Repository $cache,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->userRepository = $userRepository;
        $this->sizeFormatter = $sizeFormatter;
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
    }

    public function show(User $user): Response
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

    /**
     * @return Response|RedirectResponse
     */
    public function edit(User $user): BaseResponse
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->guard->user();
        if (false === $user->is($loggedInUser)) {
            return $this->responseFactory->redirectToRoute('users.edit', $loggedInUser);
        }

        $locales = Locale::all();

        return $this->responseFactory->view('users.edit', compact('user', 'locales'));
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        Translator $translator,
        Application $application,
        Redirector $redirector
    ): RedirectResponse {
        $user->update([
            'email' => $request->input('email'),
            'locale_id' => $request->input('locale_id'),
            'timezone' => $request->input('timezone'),
            'torrents_per_page' => $request->input('torrents_per_page'),
        ]);

        $application->setLocale($user->language->localeShort);

        $this->cache->forget('user.' . $user->id);
        $this->cache->forget('user.' . $user->slug . '.locale');
        $this->cache->forget('user.' . $user->passkey);

        return $redirector->back()->with('success', $translator->trans('messages.common.save_changes_successful'));
    }
}
