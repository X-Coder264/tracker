<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;
use App\Http\Models\TorrentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Routing\ResponseFactory;

class TorrentCommentController extends Controller
{
    /**
     * @param Torrent $torrent
     * @param ResponseFactory $responseFactory
     * @return Response
     */
    public function create(Torrent $torrent, ResponseFactory $responseFactory): Response
    {
        return $responseFactory->view('torrent-comments.create', compact('torrent'));
    }

    /**
     * @param Request $request
     * @param Torrent $torrent
     * @param AuthManager $authManager
     * @param Redirector $redirector
     * @return RedirectResponse
     */
    public function store(Request $request, Torrent $torrent, AuthManager $authManager, Redirector $redirector): RedirectResponse
    {
        $this->validate(
            $request,
            [
                'comment' => 'required|string',
            ],
            [
                'comment.required' => 'X',
            ]
        );

        $torrentComment = new TorrentComment();
        $torrentComment->user_id = $authManager->id();
        $torrentComment->comment = $request->input('comment');

        $torrent->comments()->save($torrentComment);

        return $redirector->route('torrents.show', $torrent)->with('torrentCommentSuccess', 'Bla');
    }
}
