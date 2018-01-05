<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Services\PasskeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\TorrentInfoService;
use App\Http\Services\TorrentUploadService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class TorrentController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        Cache::forget('torrents');
        $torrents = Cache::remember('torrents', 10, function () {
            return Torrent::with(['uploader'])->where('seeders', '>', 0)
                                              ->orderby('id', 'desc')
                                              ->paginate(3);
        });

        return response()->view('torrents.index', compact('torrents'));
    }

    /**
     * @return Response
     */
    public function create(): Response
    {
        return response()->view('torrents.create');
    }

    /**
     * @param Torrent            $torrent
     * @param TorrentInfoService $torrentInfoService
     *
     * @return Response
     */
    public function show(Torrent $torrent, TorrentInfoService $torrentInfoService): Response
    {
        try {
            $torrentFileNamesAndSizes = $torrentInfoService->getTorrentFileNamesAndSizes($torrent);
        } catch (FileNotFoundException $e) {
            abort(Response::HTTP_NOT_FOUND, __('messages.torrent-file-missing.error-message'));
        }

        $torrent->load(['uploader', 'peers.user']);
        $numberOfPeers = $torrent->peers->count();
        $torrentComments = $torrent->comments()->with('user')->paginate(10);

        return response()->view(
            'torrents.show',
            compact('torrent', 'numberOfPeers', 'torrentFileNamesAndSizes', 'torrentComments')
        );
    }

    /**
     * @param Request              $request
     * @param TorrentUploadService $torrentUploadService
     *
     * @return RedirectResponse
     */
    public function store(Request $request, TorrentUploadService $torrentUploadService): RedirectResponse
    {
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

        return redirect()->route('torrents.show', $torrent)
                         ->with('success', __('messages.torrents.store-successfully-uploaded-torrent.message'));
    }

    /**
     * @param Torrent          $torrent
     * @param BencodingService $encoder
     * @param BdecodingService $decoder
     * @param PasskeyService   $passkeyService
     *
     * @return Response
     */
    public function download(
        Torrent $torrent,
        BencodingService $encoder,
        BdecodingService $decoder,
        PasskeyService $passkeyService
    ): Response {
        try {
            $torrentFile = Storage::disk('public')->get("torrents/{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            abort(Response::HTTP_NOT_FOUND, __('messages.torrent-file-missing.error-message'));
        }

        $decodedTorrent = $decoder->decode($torrentFile);

        $passkey = Auth::user()->passkey;
        if (empty($passkey)) {
            $passkey = $passkeyService->generateUniquePasskey();
            User::where('id', '=', Auth::id())->update(['passkey' => $passkey]);
        }

        $decodedTorrent['announce'] = route('announce', ['passkey' => $passkey]);

        $response = new Response($encoder->encode($decodedTorrent));
        $response->headers->set('Content-Type', 'application/x-bittorrent');
        // TODO: add support for adding a prefix (or suffix) to the name of the file
        $fileName = str_replace(['/', '\\'], '', $torrent->name . '.torrent');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            mb_convert_encoding(str_replace('%', '', $fileName), 'ASCII')
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
