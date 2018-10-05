<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Torrent;
use Illuminate\Http\Response;
use App\Models\TorrentComment;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TorrentCommentRequest;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

class TorrentCommentController extends Controller
{
    /**
     * @param Torrent         $torrent
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function create(Torrent $torrent, ResponseFactory $responseFactory): Response
    {
        $torrentComment = new TorrentComment();

        return $responseFactory->view('torrent-comments.create', compact('torrent', 'torrentComment'));
    }

    /**
     * @param TorrentCommentRequest $request
     * @param Torrent               $torrent
     * @param AuthManager           $authManager
     * @param Redirector            $redirector
     * @param Translator            $translator
     * @param CacheManager          $cacheManager
     *
     * @return RedirectResponse
     */
    public function store(
        TorrentCommentRequest $request,
        Torrent $torrent,
        AuthManager $authManager,
        Redirector $redirector,
        Translator $translator,
        CacheManager $cacheManager
    ): RedirectResponse {
        $torrentComment = new TorrentComment();
        $torrentComment->user_id = $authManager->guard()->id();
        $torrentComment->comment = $request->input('comment');

        $torrent->comments()->save($torrentComment);

        $cacheManager->delete('torrent.' . $torrent->id . '.comments');

        return $redirector->route('torrents.show', $torrent)
            ->with('torrentCommentSuccess', $translator->trans('messages.torrent-comments.create-success-message'));
    }

    /**
     * @param TorrentComment  $torrentComment
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function edit(TorrentComment $torrentComment, ResponseFactory $responseFactory): Response
    {
        return $responseFactory->view('torrent-comments.edit', compact('torrentComment'));
    }

    /**
     * @param TorrentCommentRequest $request
     * @param TorrentComment        $torrentComment
     * @param Redirector            $redirector
     * @param Translator            $translator
     * @param CacheManager          $cacheManager
     *
     * @return RedirectResponse
     */
    public function update(
        TorrentCommentRequest $request,
        TorrentComment $torrentComment,
        Redirector $redirector,
        Translator $translator,
        CacheManager $cacheManager
    ): RedirectResponse {
        $torrentComment->update(['comment' => $request->input('comment')]);

        $cacheManager->delete('torrent.' . $torrentComment->torrent_id . '.comments');

        return $redirector->route('torrents.show', $torrentComment->torrent)
            ->with('torrentCommentSuccess', $translator->trans('messages.torrent-comments.update-success-message'));
    }
}
