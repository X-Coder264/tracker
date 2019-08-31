<?php

declare(strict_types=1);

namespace App\Http\Controllers\TorrentComments;

use Illuminate\Http\Response;
use App\Models\TorrentComment;
use Illuminate\Contracts\Routing\ResponseFactory;

final class EditController
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(TorrentComment $torrentComment): Response
    {
        return $this->responseFactory->view('torrent-comments.edit', compact('torrentComment'));
    }
}
