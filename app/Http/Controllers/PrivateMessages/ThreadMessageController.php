<?php

declare(strict_types=1);

namespace App\Http\Controllers\PrivateMessages;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use App\Models\PrivateMessages\Thread;
use Illuminate\Contracts\Cache\Repository;
use App\Models\PrivateMessages\ThreadMessage;
use App\Models\PrivateMessages\ThreadParticipant;
use Illuminate\Contracts\Routing\ResponseFactory;

class ThreadMessageController
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

    public function __construct(
        Guard $guard,
        Repository $cache,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
    }

    public function create(Thread $thread): Response
    {
        $threadMessage = new ThreadMessage();

        return $this->responseFactory->view(
            'private-messages.message-create',
            compact('thread', 'threadMessage')
        );
    }

    public function store(Request $request, Thread $thread): RedirectResponse
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
