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
use Illuminate\Auth\AuthManager;
use App\Services\PasskeyGenerator;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use App\Services\TorrentInfoService;
use Illuminate\Http\RedirectResponse;
use App\Services\TorrentUploadManager;
use App\Http\Requests\TorrentUploadRequest;
use App\Exceptions\FileNotWritableException;
use Illuminate\Contracts\Filesystem\Factory;
use App\Services\FileSizeCollectionFormatter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TorrentController extends Controller
{
    /**
     * @param Request         $request
     * @param AuthManager     $authManager
     * @param CacheManager    $cacheManager
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function index(
        Request $request,
        CacheManager $cacheManager,
        AuthManager $authManager,
        ResponseFactory $responseFactory
    ): Response {
        $page = (int) $request->input('page', 1);

        if (0 === $page) {
            $page = 1;
        }

        /** @var User $user */
        $user = $authManager->guard()->user();
        $torrentPerPage = $user->torrents_per_page;

        $torrents = $cacheManager->tags('torrents')->remember(
            'torrents.page.' . $page . '.perPage.' . $torrentPerPage,
            Cache::TEN_MINUTES,
            function () use ($authManager, $torrentPerPage) {
                return Torrent::with(['uploader'])->where('seeders', '>', 0)
                                              ->orderBy('id', 'desc')
                                              ->paginate($torrentPerPage);
            }
        );

        return $responseFactory->view('torrents.index', compact('torrents'));
    }

    /**
     * @param CacheManager    $cacheManager
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function create(CacheManager $cacheManager, ResponseFactory $responseFactory): Response
    {
        $categories = $cacheManager->remember('torrentCategories', Cache::THIRTY_MINUTES, function () {
            return TorrentCategory::all();
        });

        return $responseFactory->view('torrents.create', compact('categories'));
    }

    /**
     * @param Torrent            $torrent
     * @param TorrentInfoService $torrentInfoService
     * @param ResponseFactory    $responseFactory
     * @param Translator         $translator
     * @param FilesystemManager  $filesystemManager
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function show(
        Torrent $torrent,
        TorrentInfoService $torrentInfoService,
        ResponseFactory $responseFactory,
        Translator $translator,
        FilesystemManager $filesystemManager,
        FileSizeCollectionFormatter $fileSizeCollectionFormatter
    ): Response {
        try {
            $torrentFileNamesAndSizes = $torrentInfoService->getTorrentFileNamesAndSizes($torrent);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($translator->trans('messages.torrent-file-missing.error-message'));
        }

        $filesCount = count($torrentFileNamesAndSizes);

        $torrentFileNamesAndSizes = $fileSizeCollectionFormatter->format($torrentFileNamesAndSizes);

        $torrent->load(['uploader', 'peers.user']);
        $numberOfPeers = $torrent->peers->count();

        $torrentComments = $torrent->comments()->with('user')->paginate(10);

        $imdbData = $torrentInfoService->getTorrentIMDBData($torrent);
        $posterExists = $imdbData ? $filesystemManager->disk('imdb-images')->exists("{$imdbData->imdbid()}.jpg") : false;

        return $responseFactory->view(
            'torrents.show',
            compact('torrent', 'numberOfPeers', 'torrentFileNamesAndSizes', 'torrentComments', 'filesCount', 'imdbData', 'posterExists')
        );
    }

    /**
     * @param TorrentUploadRequest $request
     * @param TorrentUploadManager $torrentUploadManager
     * @param CacheManager         $cacheManager
     * @param Redirector           $redirector
     * @param Translator           $translator
     *
     * @throws FileNotWritableException
     * @throws FileNotFoundException
     *
     * @return RedirectResponse
     */
    public function store(
        TorrentUploadRequest $request,
        TorrentUploadManager $torrentUploadManager,
        CacheManager $cacheManager,
        Redirector $redirector,
        Translator $translator
    ): RedirectResponse {
        $torrent = $torrentUploadManager->upload($request);

        $cacheManager->tags('torrents')->flush();

        return $redirector->route('torrents.show', $torrent)
                         ->with('success', $translator->trans('messages.torrents.store-successfully-uploaded-torrent.message'));
    }

    /**
     * @param Torrent          $torrent
     * @param Bencoder         $encoder
     * @param Bdecoder         $decoder
     * @param PasskeyGenerator $passkeyGenerator
     * @param AuthManager      $authManager
     * @param UrlGenerator     $urlGenerator
     * @param Factory          $filesystem
     * @param Translator       $translator
     *
     * @return Response
     */
    public function download(
        Torrent $torrent,
        Bencoder $encoder,
        Bdecoder $decoder,
        PasskeyGenerator $passkeyGenerator,
        AuthManager $authManager,
        UrlGenerator $urlGenerator,
        Factory $filesystem,
        Translator $translator
    ): Response {
        try {
            $torrentFile = $filesystem->disk('torrents')->get("{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($translator->trans('messages.torrent-file-missing.error-message'));
        }

        $decodedTorrent = $decoder->decode($torrentFile);

        $passkey = $authManager->guard()->user()->passkey;

        if (empty($passkey)) {
            $passkey = $passkeyGenerator->generateUniquePasskey();
            User::where('id', '=', $authManager->guard()->id())->update(['passkey' => $passkey]);
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
