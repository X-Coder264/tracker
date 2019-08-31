<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Contracts\Routing\UrlGenerator;
use App\Repositories\TorrentCategoryRepository;
use Illuminate\Contracts\Routing\ResponseFactory;

final class CreateController
{
    /**
     * @var TorrentCategoryRepository
     */
    private $torrentCategoryRepository;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

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
