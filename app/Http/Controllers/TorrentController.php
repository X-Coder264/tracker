<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\User;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Str;
use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use App\Services\PasskeyGenerator;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Redirector;
use App\Services\TorrentInfoService;
use Illuminate\Http\RedirectResponse;
use App\Services\TorrentUploadService;
use App\Exceptions\FileNotWritableException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TorrentController extends Controller
{
    /**
     * @param Request         $request
     * @param CacheManager    $cacheManager
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function index(Request $request, CacheManager $cacheManager, ResponseFactory $responseFactory): Response
    {
        $cacheManager->forget('torrents');
        $torrents = $cacheManager->remember('torrents', 10, function () {
            return Torrent::with(['uploader'])->where('seeders', '>', 0)
                                              ->orderBy('id', 'desc')
                                              ->paginate(3);
        });

        return $responseFactory->view('torrents.index', compact('torrents'));
    }

    /**
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function create(ResponseFactory $responseFactory): Response
    {
        return $responseFactory->view('torrents.create');
    }

    /**
     * @param Torrent            $torrent
     * @param TorrentInfoService $torrentInfoService
     * @param ResponseFactory    $responseFactory
     * @param Translator         $translator
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function show(
        Torrent $torrent,
        TorrentInfoService $torrentInfoService,
        ResponseFactory $responseFactory,
        Translator $translator
    ): Response {
        try {
            $torrentFileNamesAndSizes = $torrentInfoService->getTorrentFileNamesAndSizes($torrent);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($translator->trans('messages.torrent-file-missing.error-message'));
        }

        $torrent->load(['uploader', 'peers.user']);
        $numberOfPeers = $torrent->peers->count();
        $torrentComments = $torrent->comments()->with('user')->paginate(10);

        return $responseFactory->view(
            'torrents.show',
            compact('torrent', 'numberOfPeers', 'torrentFileNamesAndSizes', 'torrentComments')
        );
    }

    /**
     * @param Request              $request
     * @param TorrentUploadService $torrentUploadService
     * @param Redirector           $redirector
     * @param Translator           $translator
     *
     * @throws FileNotWritableException
     *
     * @return RedirectResponse
     */
    public function store(
        Request $request,
        TorrentUploadService $torrentUploadService,
        Redirector $redirector,
        Translator $translator
    ): RedirectResponse {
        $this->validate(
            $request,
            [
                'torrent'     => 'required|file|mimetypes:application/x-bittorrent',
                'name'        => 'required|string|min:5|max:255|unique:torrents',
                'description' => 'required|string',
            ],
            [
                'torrent.required'     => 'Test',
                'torrent.file'         => 'Test',
                'torrent.mimetypes'    => 'Test',
                'name.required'        => 'Test',
                'name.min'             => 'Test',
                'name.max'             => 'Test',
                'name.unique'          => 'Test',
                'description.required' => 'Test',
            ]
        );

        $torrent = $torrentUploadService->upload($request);

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
            $torrentFile = $filesystem->disk('public')->get("torrents/{$torrent->id}.torrent");
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
