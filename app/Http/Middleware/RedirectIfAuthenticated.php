<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Routing\UrlGenerator;

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
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @param AuthManager $authManager
     * @param Redirector $redirector
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(AuthManager $authManager, Redirector $redirector, UrlGenerator $urlGenerator)
    {
        $this->authManager = $authManager;
        $this->redirector = $redirector;
        $this->urlGenerator = $urlGenerator;
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
            return $this->redirector->to($this->urlGenerator->route('home.index'));
        }

        return $next($request);
    }
}
