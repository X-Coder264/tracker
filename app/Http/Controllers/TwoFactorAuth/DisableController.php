<?php

declare(strict_types=1);

namespace App\Http\Controllers\TwoFactorAuth;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

final class DisableController
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
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Guard $guard, Translator $translator, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function disable(): RedirectResponse
    {
        /** @var User $user */
        $user = $this->guard->user();
        $user->is_two_factor_enabled = false;
        $user->save();

        return $this->responseFactory->redirectToRoute('2fa.status')
            ->with('success', $this->translator->trans('messages.2fa.successfully_disabled.message'));
    }
}