<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use App\Http\Requests\TorrentCommentRequest;
use App\Models\Torrent;
use App\Models\TorrentComment;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;

final class StoreController
{
    private Guard $guard;
    private Repository $cache;
    private Translator $translator;
    private ResponseFactory $responseFactory;

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

        /** @var TaggedCache $taggedCache */
        $taggedCache = $this->cache->tags([sprintf('torrent.%d', $torrentComment->torrent_id)]);
        $taggedCache->flush();

        return $this->responseFactory->redirectToRoute('torrents.show', $torrent)
            ->with('torrentCommentSuccess', $this->translator->get('messages.torrent-comments.create-success-message'));
    }
}
