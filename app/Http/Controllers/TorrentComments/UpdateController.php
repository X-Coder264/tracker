<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use App\Http\Requests\TorrentCommentRequest;
use App\Models\TorrentComment;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;

final class UpdateController
{
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

    public function __construct(Repository $cache, Translator $translator, ResponseFactory $responseFactory)
    {
        $this->cache = $cache;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(TorrentCommentRequest $request, TorrentComment $torrentComment): RedirectResponse
    {
        $torrentComment->update(['comment' => $request->input('comment')]);

        $this->cache->delete('torrent.' . $torrentComment->torrent_id . '.comments');

        return $this->responseFactory->redirectToRoute('torrents.show', $torrentComment->torrent)
            ->with('torrentCommentSuccess', $this->translator->get('messages.torrent-comments.update-success-message'));
    }
}
