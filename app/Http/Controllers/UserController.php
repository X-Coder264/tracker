<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Locale;
use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use App\Http\Requests\UpdateUserRequest;
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
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Guard $guard, UserRepository $userRepository, SizeFormatter $sizeFormatter, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->userRepository = $userRepository;
        $this->sizeFormatter = $sizeFormatter;
        $this->responseFactory = $responseFactory;
    }

    public function show(User $user): Response
    {
        $totalSeedingSize = $this->sizeFormatter->getFormattedSize($this->userRepository->getTotalSeedingSize($user->id));

        return $this->responseFactory->view('users.show', compact('user', 'totalSeedingSize'));
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
        CacheManager $cache,
        Redirector $redirector
    ): RedirectResponse {
        $user->update([
            'email' => $request->input('email'),
            'locale_id' => $request->input('locale_id'),
            'timezone' => $request->input('timezone'),
            'torrents_per_page' => $request->input('torrents_per_page'),
        ]);

        $application->setLocale($user->language->localeShort);

        $cache->forget('user.' . $user->id);
        $cache->forget('user.' . $user->slug . '.locale');
        $cache->forget('user.' . $user->passkey);

        return $redirector->back()->with('success', $translator->trans('messages.common.save_changes_successful'));
    }
}
