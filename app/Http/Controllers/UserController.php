<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
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
     * @param Request      $request
     * @param User         $user
     * @param Translator   $translator
     * @param Application  $application
     * @param CacheManager $cacheManager
     * @param Redirector   $redirector
     *
     * @return RedirectResponse
     */
    public function update(Request $request, User $user, Translator $translator, Application $application, CacheManager $cacheManager, Redirector $redirector): RedirectResponse
    {
        $locales = Locale::select('id')->get();
        $localeIDs = $locales->pluck('id')->toArray();

        $this->validate(
            $request,
            [
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'locale_id' => [
                    'required',
                    Rule::in($localeIDs),
                ],
                'timezone' => 'required|timezone',
            ],
            [
                'email.required' => $translator->trans('messages.validation.variable.required', ['var' => 'email']),
                'email.email' => $translator->trans('messages.validation.variable.email'),
                'email.unique' => $translator->trans('messages.validation.variable.email', ['var' => 'email']),
                'locale_id.required' => $translator->trans('messages.validation.variable.required', ['var' => 'language']),
                'locale_id.in' => $translator->trans('messages.validation.variable.invalid_value', ['var' => 'language']),
                'timezone.required' => $translator->trans('messages.validation.variable.required', ['var' => 'timezone']),
                'timezone.timezone' => $translator->trans('messages.validation.variable.invalid_value', ['var' => 'timezone']),
            ]
        );

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
