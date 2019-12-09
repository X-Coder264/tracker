<?php

declare(strict_types=1);

namespace App\Http\Controllers\TwoFactorAuth;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;

final class EnableController
{
    private Guard $guard;
    private Translator $translator;
    private ResponseFactory $responseFactory;

    public function __construct(Guard $guard, Translator $translator, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(): RedirectResponse
    {
        /** @var User $user */
        $user = $this->guard->user();
        $user->is_two_factor_enabled = true;
        $user->save();

        return $this->responseFactory->redirectToRoute('2fa.status')
            ->with('success', $this->translator->get('messages.2fa.successfully_enabled.message'));
    }
}
