<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use App\Http\Requests\TorrentCommentRequest;
use App\Models\TorrentComment;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;

final class UpdateController
{
    private Repository $cache;
    private Translator $translator;
    private ResponseFactory $responseFactory;

    public function __construct(Repository $cache, Translator $translator, ResponseFactory $responseFactory)
    {
        $this->cache = $cache;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(TorrentCommentRequest $request, TorrentComment $torrentComment): RedirectResponse
    {
        $torrentComment->update(['comment' => $request->input('comment')]);

        /** @var TaggedCache $taggedCache */
        $taggedCache = $this->cache->tags([sprintf('torrent.%d', $torrentComment->torrent_id)]);
        $taggedCache->flush();

        return $this->responseFactory->redirectToRoute('torrents.show', $torrentComment->torrent)
            ->with('torrentCommentSuccess', $this->translator->get('messages.torrent-comments.update-success-message'));
    }
}
