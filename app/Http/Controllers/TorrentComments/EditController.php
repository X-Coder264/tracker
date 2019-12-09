<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use App\Models\TorrentComment;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

final class EditController
{
    private ResponseFactory $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(TorrentComment $torrentComment): Response
    {
        return $this->responseFactory->view('torrent-comments.edit', compact('torrentComment'));
    }
}
