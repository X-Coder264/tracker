<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invite;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

final class InviteController
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

    public function create(): Response
    {
        /** @var User $user */
        $user = $this->guard->user();
        $user->load(['invites', 'invitees']);
        $invites = $user->invites;
        $invitees = $user->invitees;

        return $this->responseFactory->view('invites.create', compact('user', 'invites', 'invitees'));
    }

    public function store(): RedirectResponse
    {
        /** @var User $user */
        $user = $this->guard->user();

        if ($user->invites_amount < 1) {
            return $this->responseFactory->redirectToRoute('invites.create')
                ->with('error', $this->translator->trans('messages.invites_no_invites_left_error_message'));
        }

        $expiresInDays = 3;

        $invite = new Invite();
        $invite->user()->associate($user);
        $invite->code = bin2hex(random_bytes(20));
        $invite->expires_at = CarbonImmutable::now()->addDays($expiresInDays);
        $invite->save();

        $user->invites_amount = $user->invites_amount - 1;
        $user->save();

        return $this->responseFactory->redirectToRoute('invites.create')
            ->with('success', $this->translator->transChoice('messages.invites_successfully_created_message', $expiresInDays));
    }
}
