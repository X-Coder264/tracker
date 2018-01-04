<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\TorrentInfoService;
use App\Http\Services\TorrentUploadService;
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
            return Torrent::with(['uploader'])->orderby('id', 'desc')->paginate(3);
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
            abort(404, __('messages.torrent-file-missing.error-message'));
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

        return redirect()->route('torrents.show', $torrent)->with('success', 'Bla');
    }

    /**
     * @param Torrent          $torrent
     * @param BencodingService $encoder
     * @param BdecodingService $decoder
     *
     * @return Response
     */
    public function download(Torrent $torrent, BencodingService $encoder, BdecodingService $decoder): Response
    {
        try {
            $torrentFile = Storage::disk('public')->get("torrents/{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            abort(404, 'Error, you requested an unavailable .torrent file.');
        }

        $decodedTorrent = $decoder->decode($torrentFile);
        $decodedTorrent['announce'] = route('announce', ['passkey' => Auth::user()->passkey]);

        return response(
            $encoder->encode($decodedTorrent),
            200,
            [
                'Content-Type'        => 'application/x-bittorrent',
                'Content-Disposition' => 'attachment; filename="' . $torrent->name . '.torrent"',
            ]
        );
    }
}
