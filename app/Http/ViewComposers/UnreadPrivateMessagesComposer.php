<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Auth\Guard;
use App\Repositories\PrivateMessages\ThreadParticipantRepositoryInterface;

class UnreadPrivateMessagesComposer
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var ThreadParticipantRepositoryInterface
     */
    private $threadParticipantRepository;

    public function __construct(Guard $guard, ThreadParticipantRepositoryInterface $threadParticipantRepository)
    {
        $this->guard = $guard;
        $this->threadParticipantRepository = $threadParticipantRepository;
    }

    public function compose(View $view): void
    {
        if (true === $this->guard->check()) {
            $userId = $this->guard->id();
            $unreadThreadsCount = $this->threadParticipantRepository->getUnreadThreadsForUser($userId)->count();

            $view->with('hasUnreadThreads', $unreadThreadsCount > 0);
        }
    }
}
