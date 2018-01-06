<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\App;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    /**
     * @param User $user
     *
     * @return Response
     */
    public function edit(User $user): Response
    {
        $locales = Locale::all();

        return response()->view('users.edit', compact('user', 'locales'));
    }

    /**
     * @param Request $request
     * @param User    $user
     *
     * @return RedirectResponse
     */
    public function update(Request $request, User $user): RedirectResponse
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
                'email.required' => __('messages.validation.variable.required', ['var' => 'email']),
                'email.email' => __('messages.validation.variable.email'),
                'email.unique' => __('messages.validation.variable.email', ['var' => 'email']),
                'locale_id.required' => __('messages.validation.variable.required', ['var' => 'language']),
                'locale_id.in' => __('messages.validation.variable.invalid_value', ['var' => 'language']),
                'timezone.required' => __('messages.validation.variable.required', ['var' => 'timezone']),
                'timezone.timezone' => __('messages.validation.variable.invalid_value', ['var' => 'timezone']),
            ]
        );

        $user->update([
            'email' => $request->input('email'),
            'locale_id' => $request->input('locale_id'),
            'timezone' => $request->input('timezone'),
        ]);

        App::setLocale($user->language->localeShort);

        Cache::forget('user.' . $user->id);
        Cache::forget('user.' . $user->slug . '.locale');
        Cache::forget('user.' . $user->passkey);

        return back()->with('success', __('messages.common.save_changes_successful'));
    }
}
