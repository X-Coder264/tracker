<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invites;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

final class CreateController
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

    public function __invoke(): Response
    {
        /** @var User $user */
        $user = $this->guard->user();

        $user->load(['invites', 'invitees']);
        $invites = $user->invites;
        $invitees = $user->invitees;

        return $this->responseFactory->view('invites.create', compact('user', 'invites', 'invitees'));
    }
}
