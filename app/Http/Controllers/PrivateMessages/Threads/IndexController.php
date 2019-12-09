<?php

declare(strict_types=1);

namespace App\Http\Controllers\PrivateMessages\Threads;

use App\Models\PrivateMessages\Thread;
use App\Repositories\PrivateMessages\ThreadParticipantRepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

final class IndexController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var ThreadParticipantRepositoryInterface
     */
    private $threadParticipantRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Guard $guard,
        ThreadParticipantRepositoryInterface $threadParticipantRepository,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->threadParticipantRepository = $threadParticipantRepository;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(): Response
    {
        $user = $this->guard->user();

        $threads = Thread::with(['creator'])->whereHas('participants', function (Builder $query) {
            $query->where('user_id', '=', $this->guard->id());
        })->paginate(20);

        $unreadThreads = $this->threadParticipantRepository->getUnreadThreadsForUser($user->getAuthIdentifier());

        return $this->responseFactory->view(
            'private-messages.thread-index',
            compact('threads', 'user', 'unreadThreads')
        );
    }
}
