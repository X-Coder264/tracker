<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckIfTheUserIsBanned
{
    /**
     * @var StatefulGuard|Guard
     */
    private Guard $guard;
    private ResponseFactory $responseFactory;
    private Translator $translator;

    public function __construct(Guard $guard, ResponseFactory $responseFactory, Translator $translator)
    {
        $this->guard = $guard;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure $next
     *
     * @return RedirectResponse|Response
     */
    public function handle(Request $request, $next)
    {
        if ($this->guard->check() && true === $this->guard->user()->banned) {
            $this->guard->logout();

            return $this->responseFactory->redirectToRoute('login')
                ->with('error', $this->translator->get('messages.user.banned'));
        }

        return $next($request);
    }
}
