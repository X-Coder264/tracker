<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastSeenAtInfo
{
    private Guard $guard;

    /**
     * @var int
     */
    const FIVE_MINUTES_IN_SECONDS = 300;

    public function __construct(Guard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure $next
     */
    public function handle(Request $request, $next): Response
    {
        if (true === $this->guard->check()) {
            /** @var User $user */
            $user = $this->guard->user();
            $lastSeenAt = $user->last_seen_at;
            if (null === $lastSeenAt || CarbonImmutable::now()->diffInSeconds($lastSeenAt) > self::FIVE_MINUTES_IN_SECONDS) {
                $user->timestamps = false;
                $user->last_seen_at = CarbonImmutable::now();
                $user->update();
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
