<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

final class UpdateController
{
    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Redirector
     */
    private $redirector;

    public function __construct(Repository $cache, Translator $translator, Application $application, Redirector $redirector)
    {
        $this->cache = $cache;
        $this->translator = $translator;
        $this->application = $application;
        $this->redirector = $redirector;
    }

    public function __invoke(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'email' => $request->input('email'),
            'locale_id' => $request->input('locale_id'),
            'timezone' => $request->input('timezone'),
            'torrents_per_page' => $request->input('torrents_per_page'),
        ]);

        $this->application->setLocale($user->language->localeShort);

        $this->cache->forget('user.' . $user->id);
        $this->cache->forget('user.' . $user->slug . '.locale');
        $this->cache->forget('user.' . $user->passkey);

        return $this->redirector->back()->with('success', $this->translator->get('messages.common.save_changes_successful'));
    }
}
