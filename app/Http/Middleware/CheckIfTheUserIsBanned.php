<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Translation\Translator;

class CheckIfTheUserIsBanned
{
    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var Redirector
     */
    private $redirector;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(AuthManager $authManager, Redirector $redirector, Translator $translator)
    {
        $this->authManager = $authManager;
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
        if ($this->authManager->guard()->check() && true === $this->authManager->guard()->user()->banned) {
            $this->authManager->guard()->logout();

            return $this->redirector->route('login')->with('error', $this->translator->trans('messages.user.banned'));
        }

        return $next($request);
    }
}
