<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Enumerations\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TorrentCategory;
use App\Services\PasskeyGenerator;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use App\Services\TorrentInfoService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use App\Services\TorrentUploadManager;
use App\Http\Requests\TorrentUploadRequest;
use App\Exceptions\FileNotWritableException;
use App\Services\FileSizeCollectionFormatter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TorrentController extends Controller
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    public function __construct(
        CacheManager $cacheManager,
        Guard $guard,
        ResponseFactory $responseFactory,
        Translator $translator,
        FilesystemManager $filesystemManager
    ) {
        $this->cacheManager = $cacheManager;
        $this->guard = $guard;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->filesystemManager = $filesystemManager;
    }

    public function index(Request $request): Response
    {
        $page = (int) $request->input('page', 1);

        if (0 === $page) {
            $page = 1;
        }

        /** @var User $user */
        $user = $this->guard->user();
        $torrentPerPage = $user->torrents_per_page;

        $torrents = $this->cacheManager->tags('torrents')->remember(
            'torrents.page.' . $page . '.perPage.' . $torrentPerPage,
            Cache::TEN_MINUTES,
            function () use ($torrentPerPage): LengthAwarePaginator {
                return Torrent::with(['uploader'])->where('seeders', '>', 0)
                                              ->orderBy('id', 'desc')
                                              ->paginate($torrentPerPage);
            }
        );

        return $this->responseFactory->view('torrents.index', compact('torrents'));
    }

    public function create(): Response
    {
        $categories = $this->cacheManager->remember('torrentCategories', Cache::THIRTY_MINUTES, function () {
            return TorrentCategory::all();
        });

        return $this->responseFactory->view('torrents.create', compact('categories'));
    }

    public function show(
        Torrent $torrent,
        TorrentInfoService $torrentInfoService,
        FileSizeCollectionFormatter $fileSizeCollectionFormatter
    ): Response {
        try {
            $torrentFileNamesAndSizes = $torrentInfoService->getTorrentFileNamesAndSizes($torrent);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($this->translator->trans('messages.torrent-file-missing.error-message'));
        }

        $filesCount = count($torrentFileNamesAndSizes);

        $torrentFileNamesAndSizes = $fileSizeCollectionFormatter->format($torrentFileNamesAndSizes);

        /** @var Torrent $torrent */
        $torrent = $this->cacheManager->remember('torrent.' . $torrent->id, Cache::ONE_DAY, function () use ($torrent): Torrent {
            return $torrent->load(['uploader', 'peers.user', 'category', 'infoHashes']);
        });

        $numberOfPeers = $torrent->peers->count();

        /** @var LengthAwarePaginator $torrentComments */
        $torrentComments = $this->cacheManager->remember('torrent.' . $torrent->id . '.comments', Cache::ONE_DAY, function () use ($torrent): LengthAwarePaginator {
            return $torrent->comments()->with('user')->paginate(10);
        });

        $imdbData = $torrentInfoService->getTorrentIMDBData($torrent);
        $posterExists = $imdbData ? $this->filesystemManager->disk('imdb-images')->exists("{$imdbData->imdbid()}.jpg") : false;

        return $this->responseFactory->view(
            'torrents.show',
            compact(
                'torrent',
                'numberOfPeers',
                'torrentFileNamesAndSizes',
                'torrentComments',
                'filesCount',
                'imdbData',
                'posterExists'
            )
        );
    }

    /**
     * @throws FileNotWritableException
     * @throws FileNotFoundException
     */
    public function store(
        TorrentUploadRequest $request,
        TorrentUploadManager $torrentUploadManager,
        Redirector $redirector
    ): RedirectResponse {
        $torrent = $torrentUploadManager->upload($request);

        $this->cacheManager->tags('torrents')->flush();

        return $redirector->route('torrents.show', $torrent)
                         ->with('success', $this->translator->trans('messages.torrents.store-successfully-uploaded-torrent.message'));
    }

    public function download(
        Torrent $torrent,
        Bencoder $encoder,
        Bdecoder $decoder,
        PasskeyGenerator $passkeyGenerator,
        UrlGenerator $urlGenerator
    ): Response {
        try {
            $torrentFile = $this->filesystemManager->disk('torrents')->get("{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($this->translator->trans('messages.torrent-file-missing.error-message'));
        }

        $decodedTorrent = $decoder->decode($torrentFile);

        $passkey = $this->guard->user()->passkey;

        if (empty($passkey)) {
            $passkey = $passkeyGenerator->generateUniquePasskey();
            User::where('id', '=', $this->guard->id())->update(['passkey' => $passkey]);
        }

        $decodedTorrent['announce'] = $urlGenerator->route('announce', ['passkey' => $passkey]);

        $response = new Response($encoder->encode($decodedTorrent));
        $response->headers->set('Content-Type', 'application/x-bittorrent');
        // TODO: add support for adding a prefix (or suffix) to the name of the file
        $fileName = str_replace(['/', '\\'], '', $torrent->name . '.torrent');
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            str_replace('%', '', Str::ascii($fileName))
        );

        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
