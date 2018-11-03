<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Torrent;
use Illuminate\Http\Response;
use App\Models\TorrentComment;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Cache\Repository;
use App\Http\Requests\TorrentCommentRequest;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

class TorrentCommentController extends Controller
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Redirector
     */
    private $redirector;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var Guard
     */
    private $guard;

    public function __construct(
        ResponseFactory $responseFactory,
        Redirector $redirector,
        Translator $translator,
        Repository $cache,
        Guard $guard
    ) {
        $this->responseFactory = $responseFactory;
        $this->redirector = $redirector;
        $this->translator = $translator;
        $this->cache = $cache;
        $this->guard = $guard;
    }

    public function create(Torrent $torrent): Response
    {
        $torrentComment = new TorrentComment();

        return $this->responseFactory->view('torrent-comments.create', compact('torrent', 'torrentComment'));
    }

    public function store(TorrentCommentRequest $request, Torrent $torrent): RedirectResponse
    {
        $torrentComment = new TorrentComment();
        $torrentComment->user_id = $this->guard->id();
        $torrentComment->comment = $request->input('comment');

        $torrent->comments()->save($torrentComment);

        $this->cache->delete('torrent.' . $torrent->id . '.comments');

        return $this->redirector->route('torrents.show', $torrent)
            ->with('torrentCommentSuccess', $this->translator->trans('messages.torrent-comments.create-success-message'));
    }

    public function edit(TorrentComment $torrentComment): Response
    {
        return $this->responseFactory->view('torrent-comments.edit', compact('torrentComment'));
    }

    public function update(TorrentCommentRequest $request, TorrentComment $torrentComment): RedirectResponse
    {
        $torrentComment->update(['comment' => $request->input('comment')]);

        $this->cache->delete('torrent.' . $torrentComment->torrent_id . '.comments');

        return $this->redirector->route('torrents.show', $torrentComment->torrent)
            ->with('torrentCommentSuccess', $this->translator->trans('messages.torrent-comments.update-success-message'));
    }
}
