<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use App\Models\Torrent;
use App\Models\TorrentComment;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

final class CreateController
{
    private ResponseFactory $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Torrent $torrent): Response
    {
        $torrentComment = new TorrentComment();

        return $this->responseFactory->view('torrent-comments.create', compact('torrent', 'torrentComment'));
    }
}
