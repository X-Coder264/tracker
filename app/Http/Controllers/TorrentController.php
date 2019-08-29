<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Enumerations\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TorrentCategory;
use App\Services\IMDb\IMDBManager;
use Illuminate\Cache\CacheManager;
use App\Services\TorrentInfoService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use App\Services\TorrentUploadManager;
use App\Repositories\TorrentRepository;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Http\Requests\TorrentUploadRequest;
use App\Exceptions\FileNotWritableException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\Factory;
use App\Services\FileSizeCollectionFormatter;
use Illuminate\Contracts\Routing\UrlGenerator;
use App\Repositories\TorrentCategoryRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TorrentController
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

    /**
     * @var TorrentCategoryRepository
     */
    private $torrentCategoryRepository;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var IMDBManager
     */
    private $IMDBManager;

    /**
     * @var Gate
     */
    private $gate;

    /**
     * @var TorrentRepository
     */
    private $torrentRepository;

    public function __construct(
        CacheManager $cacheManager,
        Guard $guard,
        ResponseFactory $responseFactory,
        Translator $translator,
        FilesystemManager $filesystemManager,
        TorrentCategoryRepository $torrentCategoryRepository,
        UrlGenerator $urlGenerator,
        Factory $validatorFactory,
        IMDBManager $IMDBManager,
        Gate $gate,
        TorrentRepository $torrentRepository
    ) {
        $this->cacheManager = $cacheManager;
        $this->guard = $guard;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->filesystemManager = $filesystemManager;
        $this->torrentCategoryRepository = $torrentCategoryRepository;
        $this->urlGenerator = $urlGenerator;
        $this->validatorFactory = $validatorFactory;
        $this->IMDBManager = $IMDBManager;
        $this->gate = $gate;
        $this->torrentRepository = $torrentRepository;
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
                return Torrent::with(['uploader', 'category'])->where('seeders', '>', 0)
                                              ->orderBy('id', 'desc')
                                              ->paginate($torrentPerPage);
            }
        );

        return $this->responseFactory->view('torrents.index', compact('torrents'));
    }

    public function create(): Response
    {
        $categories = $this->torrentCategoryRepository->getAllCategories();

        $torrent = new Torrent();

        $formActionUrl = $this->urlGenerator->route('torrents.store');

        return $this->responseFactory->view('torrents.create', compact('categories', 'torrent', 'formActionUrl'));
    }

    public function show(
        Request $request,
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
        $torrent = $this->cacheManager->remember('torrent.' . $torrent->id, Cache::THIRTY_MINUTES, function () use ($torrent): Torrent {
            return $torrent->load(['uploader', 'peers.user', 'category', 'infoHashes']);
        });

        $numberOfPeers = $torrent->peers->count();

        $page = (int) $request->input('page', 1);

        /** @var LengthAwarePaginator $torrentComments */
        $torrentComments = $this->cacheManager->remember(sprintf('torrent.%d.comments.page.%d', $torrent->id, $page), Cache::ONE_DAY, function () use ($torrent): LengthAwarePaginator {
            return $torrent->comments()->with('user')->paginate(10);
        });

        $imdbData = $torrentInfoService->getTorrentIMDBData($torrent);
        $posterExists = $imdbData ? $this->filesystemManager->disk('imdb-images')->exists("{$imdbData->getId()}.jpg") : false;

        $this->torrentRepository->incrementViewCountForTorrent($torrent->id);

        $user = $this->guard->user();

        return $this->responseFactory->view(
            'torrents.show',
            compact(
                'torrent',
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

    /**
     * @throws FileNotWritableException
     * @throws FileNotFoundException
     */
    public function store(
        TorrentUploadRequest $request,
        TorrentUploadManager $torrentUploadManager
    ): RedirectResponse {
        $torrent = $torrentUploadManager->upload($request);

        $this->cacheManager->tags('torrents')->flush();

        return $this->responseFactory->redirectToRoute('torrents.show', $torrent)
                         ->with('success', $this->translator->trans('messages.torrents.store-successfully-uploaded-torrent.message'));
    }

    public function edit(Torrent $torrent): BaseResponse
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

    public function update(Request $request, Torrent $torrent): RedirectResponse
    {
        try {
            $this->gate->authorize('update', $torrent);
        } catch (AuthorizationException $exception) {
            return $this->responseFactory->redirectToRoute('torrents.index')
                ->with('error', $this->translator->trans('messages.torrent.not_allowed_to_edit'));
        }

        $validator = $this->validatorFactory->make(
            $request->all(),
            [
                'name' => 'required|string|min:5|max:255|unique:torrents',
                'description' => 'required|string|min:30',
                'category' => 'required|integer|exists:torrent_categories,id',
                'imdb_url' => 'nullable|url',
            ]
        );

        if ($validator->fails()) {
            return $this->responseFactory->redirectToRoute('torrents.edit', $torrent)
                ->withErrors($validator)
                ->withInput();
        }

        $torrent->name = $request->input('name');
        $torrent->description = $request->input('description');
        $torrent->category_id = $request->input('category');

        $category = TorrentCategory::findOrFail($request->input('category'));

        if (true === $request->filled('imdb_url') && true === $category->imdb) {
            try {
                $imdbId = $this->IMDBManager->getIMDBIdFromFullURL($request->input('imdb_url'));
                $torrent->imdb_id = $imdbId;
            } catch (Exception $exception) {
            }
        }

        $torrent->save();

        return $this->responseFactory->redirectToRoute('torrents.edit', $torrent)
            ->with('success', $this->translator->trans('messages.torrent.successfully_updated'));
    }

    public function download(
        Request $request,
        Torrent $torrent,
        Bencoder $encoder,
        Bdecoder $decoder,
        UrlGenerator $urlGenerator
    ): Response {
        $user = $this->guard->user();

        if (null === $user) {
            if (! $request->filled('passkey')) {
                throw new AuthenticationException();
            }

            $passkey = $request->input('passkey');

            $user = User::where('passkey', '=', $passkey)->first();

            if (null === $user) {
                throw new AuthenticationException();
            }
        }

        try {
            $torrentFile = $this->filesystemManager->disk('torrents')->get("{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($this->translator->trans('messages.torrent-file-missing.error-message'));
        }

        $decodedTorrent = $decoder->decode($torrentFile);

        $passkey = $user->passkey;

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
