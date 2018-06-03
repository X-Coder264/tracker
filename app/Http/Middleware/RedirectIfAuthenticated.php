<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;

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

    /**
     * @param AuthManager $authManager
     * @param Redirector  $redirector
     */
    public function __construct(AuthManager $authManager, Redirector $redirector)
    {
        $this->authManager = $authManager;
        $this->redirector = $redirector;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->authManager->guard($guard)->check()) {
            return $this->redirector->route('home.index');
        }

        return $next($request);
    }
}
