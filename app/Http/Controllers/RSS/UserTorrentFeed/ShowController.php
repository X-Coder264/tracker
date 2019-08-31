<?php

declare(strict_types=1);

namespace App\Http\Controllers\RSS\UserTorrentFeed;

use Illuminate\Http\Response;
use App\Models\TorrentCategory;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ShowController
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(): Response
    {
        $categories = TorrentCategory::all();

        return $this->responseFactory->view('rss.show', compact('categories'));
    }
}
