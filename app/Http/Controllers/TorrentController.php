<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use Exception;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
    public function show(
        Torrent $torrent,
        TorrentInfoService $torrentInfoService
    ): Response {
        $torrent->load(['uploader', 'peers.user']);
        $numberOfPeers = $torrent->peers->count();

        $torrentFileNamesAndSizes = $torrentInfoService->getTorrentFileNamesAndSizes($torrent);

        return response()->view('torrents.show', compact('torrent', 'numberOfPeers', 'torrentFileNamesAndSizes'));
    }

    /**
     * @param Request              $request
     * @param TorrentUploadService $torrentUploadService
     *
     * @return RedirectResponse
     */
    public function store(Request $request, TorrentUploadService $torrentUploadService): RedirectResponse
    {
        try {
            $torrent = $torrentUploadService->upload($request);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('torrents.show', $torrent)->with('success', 'Bla');
    }

    /**
     * @param Torrent          $torrent
     * @param BencodingService $encoder
     * @param BdecodingService $decoder
     *
     * @return BinaryFileResponse
     */
    public function download(Torrent $torrent, BencodingService $encoder, BdecodingService $decoder): BinaryFileResponse
    {
        try {
            $torrentFile = Storage::disk('public')->get("torrents/{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            abort(404, 'Error, you requested an unavailable .torrent file.');
        }

        $decodedTorrent = $decoder->decode($torrentFile);
        $passkey = Auth::user()->passkey;
        $decodedTorrent['announce'] = route('announce') . '?passkey=' . $passkey;
        $filePath = "torrents/{$torrent->id}-" . Auth::id() . '.torrent';
        Storage::disk('public')->put($filePath, $encoder->encode($decodedTorrent));
        $url = Storage::url($filePath);
        $path = public_path($url);

        return response()->download(
            $path,
            $torrent->name . '.torrent',
            ['content-type' => 'application/x-bittorrent']
        )->deleteFileAfterSend(true);
    }
}
