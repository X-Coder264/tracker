<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Routing\UrlGenerator;

class Authenticate extends Middleware
{
    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    public function __construct(Auth $auth, UrlGenerator $urlGenerator)
    {
        parent::__construct($auth);

        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return $this->urlGenerator->route('login');
        }
    }
}
