<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\Torrent;
use App\Repositories\TorrentCategoryRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Response;

final class CreateController
{
    private TorrentCategoryRepository $torrentCategoryRepository;
    private UrlGenerator $urlGenerator;
    private ResponseFactory $responseFactory;

    public function __construct(
        TorrentCategoryRepository $torrentCategoryRepository,
        UrlGenerator $urlGenerator,
        ResponseFactory $responseFactory
    ) {
        $this->torrentCategoryRepository = $torrentCategoryRepository;
        $this->urlGenerator = $urlGenerator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(): Response
    {
        $categories = $this->torrentCategoryRepository->getAllCategories();

        $torrent = new Torrent();

        $formActionUrl = $this->urlGenerator->route('torrents.store');

        return $this->responseFactory->view('torrents.create', compact('categories', 'torrent', 'formActionUrl'));
    }
}
