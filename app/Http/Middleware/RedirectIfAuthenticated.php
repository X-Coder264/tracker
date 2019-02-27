<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Routing\ResponseFactory;

class RedirectIfAuthenticated
{
    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(AuthManager $authManager, ResponseFactory $responseFactory)
    {
        $this->authManager = $authManager;
        $this->responseFactory = $responseFactory;
    }

    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        if ($this->authManager->guard($guard)->check()) {
            return $this->responseFactory->redirectToRoute('home');
        }

        return $next($request);
    }
}
