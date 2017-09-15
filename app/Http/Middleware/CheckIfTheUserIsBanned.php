<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Translation\Translator;

class CheckIfTheUserIsBanned
{
    /**
     * @var StatefulGuard
     */
    private $guard;

    /**
     * @var Redirector
     */
    private $redirector;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Guard $guard, Redirector $redirector, Translator $translator)
    {
        $this->guard = $guard;
        $this->redirector = $redirector;
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

            return $this->redirector->route('login')->with('error', $this->translator->trans('messages.user.banned'));
        }

        return $next($request);
    }
}
