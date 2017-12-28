<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Models\TorrentComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class TorrentCommentController extends Controller
{
    /**
     * @param Torrent $torrent
     * @return Response
     */
    public function create(Torrent $torrent): Response
    {
        return response()->view('torrent-comments.create', compact('torrent'));
    }

    /**
     * @param Request $request
     * @param Torrent $torrent
     * @return RedirectResponse
     */
    public function store(Request $request, Torrent $torrent): RedirectResponse
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
        $torrentComment->user_id = Auth::id();
        $torrentComment->comment = $request->input('comment');

        $torrent->comments()->save($torrentComment);

        return redirect()->route('torrents.show', $torrent)->with('torrentCommentSuccess', 'Bla');
    }
}
