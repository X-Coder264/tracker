<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var Redirector
     */
    private $redirector;

    public function __construct(AuthManager $authManager, Redirector $redirector)
    {
        $this->authManager = $authManager;
        $this->redirector = $redirector;
    }

    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        if ($this->authManager->guard($guard)->check()) {
            return $this->redirector->route('home');
        }

        return $next($request);
    }
}
