<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class UserController extends Controller
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Guard $guard, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->responseFactory = $responseFactory;
    }

    public function show(User $user): Response
    {
        return $this->responseFactory->view('users.show', compact('user'));
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
