<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use Illuminate\Http\RedirectResponse;
use App\Services\TorrentUploadManager;
use Illuminate\Contracts\Cache\Repository;
use App\Http\Requests\TorrentUploadRequest;
use App\Exceptions\FileNotWritableException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

final class StoreController
{
    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var TorrentUploadManager
     */
    private $torrentUploadManager;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Repository $cache,
        TorrentUploadManager $torrentUploadManager,
        Translator $translator,
        ResponseFactory $responseFactory
    ) {
        $this->cache = $cache;
        $this->torrentUploadManager = $torrentUploadManager;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @throws FileNotWritableException
     * @throws FileNotFoundException
     */
    public function __invoke(TorrentUploadRequest $request): RedirectResponse
    {
        $torrent = $this->torrentUploadManager->upload($request);

        $this->cache->tags('torrents')->flush();

        return $this->responseFactory->redirectToRoute('torrents.show', $torrent)
            ->with('success', $this->translator->trans('messages.torrents.store-successfully-uploaded-torrent.message'));
    }
}
