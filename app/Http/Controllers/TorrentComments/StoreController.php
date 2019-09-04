<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use App\Models\Torrent;
use App\Models\TorrentComment;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Cache\Repository;
use App\Http\Requests\TorrentCommentRequest;
use Illuminate\Contracts\Translation\Translator;
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
     * @var Translator
     */
    private $translator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Guard $guard,
        Repository $cache,
        Translator $translator,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->cache = $cache;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(TorrentCommentRequest $request, Torrent $torrent): RedirectResponse
    {
        $torrentComment = new TorrentComment();
        $torrentComment->user_id = $this->guard->id();
        $torrentComment->comment = $request->input('comment');

        $torrent->comments()->save($torrentComment);

        $this->cache->delete('torrent.' . $torrent->id . '.comments');

        return $this->responseFactory->redirectToRoute('torrents.show', $torrent)
            ->with('torrentCommentSuccess', $this->translator->get('messages.torrent-comments.create-success-message'));
    }
}
