<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\Torrent;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Routing\UrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\TorrentCategoryRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;

final class EditController
{
    /**
     * @var Gate
     */
    private $gate;

    /**
     * @var TorrentCategoryRepository
     */
    private $torrentCategoryRepository;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Gate $gate,
        TorrentCategoryRepository $torrentCategoryRepository,
        UrlGenerator $urlGenerator,
        Translator $translator,
        ResponseFactory $responseFactory
    ) {
        $this->gate = $gate;
        $this->torrentCategoryRepository = $torrentCategoryRepository;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Torrent $torrent): Response
    {
        try {
            $this->gate->authorize('update', $torrent);
        } catch (AuthorizationException $exception) {
            return $this->responseFactory->redirectToRoute('torrents.index')
                ->with('error', $this->translator->trans('messages.torrent.not_allowed_to_edit'));
        }

        $categories = $this->torrentCategoryRepository->getAllCategories();

        $formActionUrl = $this->urlGenerator->route('torrents.update', $torrent);

        return $this->responseFactory->view('torrents.edit', compact('categories', 'torrent', 'formActionUrl'));
    }
}
