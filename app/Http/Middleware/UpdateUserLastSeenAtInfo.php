<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\DatabaseManager;

class UpdateUserLastSeenAtInfo
{
    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var int
     */
    const FIVE_MINUTES_IN_SECONDS = 300;

    /**
     * @param AuthManager     $authManager
     * @param DatabaseManager $databaseManager
     */
    public function __construct(AuthManager $authManager, DatabaseManager $databaseManager)
    {
        $this->authManager = $authManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, $next)
    {
        if (true === $this->authManager->guard()->check()) {
            /** @var User $user */
            $user = $this->authManager->guard()->user();
            $lastSeenAt = $user->last_seen_at;
            if (null === $lastSeenAt || Carbon::now()->diffInSeconds($lastSeenAt) > self::FIVE_MINUTES_IN_SECONDS) {
                $user->timestamps = false;
                $user->last_seen_at = Carbon::now();
                $user->update();
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
