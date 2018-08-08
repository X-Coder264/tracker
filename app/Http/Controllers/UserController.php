<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

class UserController extends Controller
{
    /**
     * @param User            $user
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function edit(User $user, ResponseFactory $responseFactory): Response
    {
        $locales = Locale::all();

        return $responseFactory->view('users.edit', compact('user', 'locales'));
    }

    /**
     * @param UpdateUserRequest $request
     * @param User              $user
     * @param Translator        $translator
     * @param Application       $application
     * @param CacheManager      $cacheManager
     * @param Redirector        $redirector
     *
     * @return RedirectResponse
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        Translator $translator,
        Application $application,
        CacheManager $cacheManager,
        Redirector $redirector
    ): RedirectResponse {
        $user->update([
            'email' => $request->input('email'),
            'locale_id' => $request->input('locale_id'),
            'timezone' => $request->input('timezone'),
        ]);

        $application->setLocale($user->language->localeShort);

        $cacheManager->forget('user.' . $user->id);
        $cacheManager->forget('user.' . $user->slug . '.locale');
        $cacheManager->forget('user.' . $user->passkey);

        return $redirector->back()->with('success', $translator->trans('messages.common.save_changes_successful'));
    }
}
