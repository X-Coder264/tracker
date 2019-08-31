<?php

declare(strict_types=1);

namespace App\Http\Controllers\PrivateMessages\ThreadMessages;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use App\Models\PrivateMessages\Thread;
use Illuminate\Contracts\Cache\Repository;
use App\Models\PrivateMessages\ThreadParticipant;
use Illuminate\Contracts\Routing\ResponseFactory;

final class StoreController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Guard $guard, Repository $cache, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, Thread $thread): RedirectResponse
    {
        /** @var User $user */
        $user = $this->guard->user();

        $thread->load('participants.user');

        $foundParticipantUser = false;
        $thread->participants->each(function (ThreadParticipant $participant) use ($user, &$foundParticipantUser) {
            if ($participant->user->is($user)) {
                $foundParticipantUser = true;
            }
        });

        if (false === $foundParticipantUser) {
            return $this->responseFactory->redirectToRoute('threads.index')->with('warning', 'Added test.');
        }

        $thread->messages()->create(
            [
                'message' => $request->input('message'),
                'user_id' => $this->guard->id(),
            ]
        );

        $thread->touch();

        $thread->participants->each(function (ThreadParticipant $participant) use ($user) {
            if ($participant->user->is($user)) {
                $participant->touch();
            }

            $this->cache->forget(sprintf('user.%d.unreadThreads', $participant->user_id));
        });

        return $this->responseFactory->redirectToRoute('threads.show', $thread)->with('success', 'Added test.');
    }
}
