<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Models\Locale;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final class EditController
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

    public function __invoke(User $user): Response
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->guard->user();

        if (false === $user->is($loggedInUser)) {
            return $this->responseFactory->redirectToRoute('users.edit', $loggedInUser);
        }

        $locales = Locale::all();

        return $this->responseFactory->view('users.edit', compact('user', 'locales'));
    }
}
