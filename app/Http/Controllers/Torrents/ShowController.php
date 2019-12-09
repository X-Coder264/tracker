<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Enumerations\Cache;
use App\Models\Torrent;
use App\Services\FileSizeCollectionFormatter;
use App\Services\TorrentInfoService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowController
{
    /**
     * @var TorrentInfoService
     */
    private $torrentInfoService;

    /**
     * @var FileSizeCollectionFormatter
     */
    private $fileSizeCollectionFormatter;

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        TorrentInfoService $torrentInfoService,
        FileSizeCollectionFormatter $fileSizeCollectionFormatter,
        Repository $cache,
        FilesystemManager $filesystemManager,
        Guard $guard,
        Translator $translator,
        ResponseFactory $responseFactory
    ) {
        $this->torrentInfoService = $torrentInfoService;
        $this->fileSizeCollectionFormatter = $fileSizeCollectionFormatter;
        $this->cache = $cache;
        $this->filesystemManager = $filesystemManager;
        $this->guard = $guard;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, Torrent $torrent): Response
    {
        try {
            $torrentFileNamesAndSizes = $this->torrentInfoService->getTorrentFileNamesAndSizes($torrent);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($this->translator->get('messages.torrent-file-missing.error-message'));
        }

        $filesCount = count($torrentFileNamesAndSizes);

        $torrentFileNamesAndSizes = $this->fileSizeCollectionFormatter->format($torrentFileNamesAndSizes);

        /** @var Torrent $cachedTorrent */
        $cachedTorrent = $this->cache->remember('torrent.' . $torrent->id, Cache::THIRTY_MINUTES, function () use ($torrent): Torrent {
            return $torrent->load(['uploader', 'peers.user', 'category', 'infoHashes']);
        });

        $numberOfPeers = $cachedTorrent->peers->count();

        $page = (int) $request->input('page', 1);

        /** @var LengthAwarePaginator $torrentComments */
        $torrentComments = $this->cache->remember(sprintf('torrent.%d.comments.page.%d', $torrent->id, $page), Cache::ONE_DAY, function () use ($torrent): LengthAwarePaginator {
            return $torrent->comments()->with('user')->paginate(10);
        });

        $imdbData = $this->torrentInfoService->getTorrentIMDBData($torrent);
        $posterExists = $imdbData ? $this->filesystemManager->disk('imdb-images')->exists("{$imdbData->getId()}.jpg") : false;

        $torrent->views_count++;
        $torrent->save();

        $user = $this->guard->user();

        return $this->responseFactory->view(
            'torrents.show',
            compact(
                'torrent',
                'cachedTorrent',
                'numberOfPeers',
                'torrentFileNamesAndSizes',
                'torrentComments',
                'filesCount',
                'imdbData',
                'posterExists',
                'user'
            )
        );
    }
}
