<?php

declare(strict_types=1);

namespace App\Http\Controllers\PrivateMessages\Threads;

use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadMessage;
use App\Models\PrivateMessages\ThreadParticipant;
use App\Repositories\PrivateMessages\ThreadParticipantRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowController
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
     * @var Repository
     */
    private $cache;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Guard $guard,
        ThreadParticipantRepositoryInterface $threadParticipantRepository,
        Repository $cache,
        UrlGenerator $urlGenerator,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->threadParticipantRepository = $threadParticipantRepository;
        $this->cache = $cache;
        $this->urlGenerator = $urlGenerator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Thread $thread): Response
    {
        $user = $this->guard->user();

        $thread->load('participants.user');

        /** @var ThreadParticipant|null $currentParticipant */
        $currentParticipant = null;
        $thread->participants->each(function (ThreadParticipant $participant) use (&$currentParticipant, $user) {
            if ($participant->user->is($user)) {
                $currentParticipant = $participant;

                return false;
            }
        });

        if (null === $currentParticipant) {
            throw new NotFoundHttpException();
        }

        $currentParticipant->last_read_at = CarbonImmutable::now();
        $currentParticipant->save();

        $unreadThreads = $this->threadParticipantRepository->getUnreadThreadsForUser($user->getAuthIdentifier());

        if ($unreadThreads->contains($thread->id)) {
            $this->cache->forget(sprintf('user.%d.unreadThreads', $user->getAuthIdentifier()));
        }

        /** @var Collection $participants */
        $participants = $thread->participants->reject(function (ThreadParticipant $participant) use ($user) {
            return $participant->user->is($user);
        });

        $participantsText = $participants->transform(function (ThreadParticipant $participant) {
            return sprintf(
                '<a href="%s">%s</a>',
                $this->urlGenerator->route('users.show', $participant->user),
                $participant->user->name
            );
        })->implode(', ');

        $messages = ThreadMessage::with('user')
            ->where('thread_id', '=', $thread->id)
            ->orderBy('id')
            ->paginate(20);

        return $this->responseFactory->view(
            'private-messages.thread-show',
            compact('thread', 'messages', 'participantsText')
        );
    }
}
