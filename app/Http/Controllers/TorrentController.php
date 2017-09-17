<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\Peer;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use App\Http\Services\TorrentUploadService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Models\Torrent;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TorrentController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        /*$peerIPAddressArray = explode('.', '5.93.165.5');
        $peerIPAddress = pack(
            "C*",
            $peerIPAddressArray[0],
            $peerIPAddressArray[1],
            $peerIPAddressArray[2],
            $peerIPAddressArray[3]
        );
        $test = inet_pton('2001:db8:a0b:12f0::1');
        $encoder = new BencodingService();
        dd($encoder->encode($peerIPAddress));
        dd($encoder->encode(inet_pton('ff05::1')));*/
        //dd(unpack("C*", $peerIPAddress));
        /*$peers = Peer::with('IPs')
            ->where('user_id', '!=', 1)
            ->select(['id', 'peer_id'])
            ->limit(50)
            ->inRandomOrder()
            ->get();
        dd($peers);*/
        /*$decoder = new BdecodingService();
        dd($decoder->decode('d8:completei2e10:incompletei2e8:intervali2400e12:min intervali900e5:peers30:]ĄíT¸đíS]ĄíS6:peers60:e'));*/
        //dd(storage_path('app'));
        $torrents = Torrent::with(['uploader'])->get();
        return response()->view('torrent.index', compact('torrents'));
    }
    /**
     * @return Response
     */
    public function create(): Response
    {
        //return hex2bin('-qB33F0-59B1pr6nyaml');
        //dd(DB::table('test')->where('peer_id', '-qB33F0-59B1pr6nyaml')->first());
        //dd(DB::table('test')->where('peer_id', '-UT2210-ÖbĎ¦{,S˘')->first());
        //return strlen(bin2hex('-qB33F0-59B1pr6nyaml'));
        return response()->view('torrent.create');
    }

    /**
     * @param Request $request
     * @param TorrentUploadService $torrentUploadService
     * @return RedirectResponse
     */
    public function store(Request $request, TorrentUploadService $torrentUploadService): RedirectResponse
    {
        /*$encoder = new BencodingService();
        dd($encoder->encode($request->file('torrent')));*/
        try {
            $torrent = $torrentUploadService->upload($request);
        } catch (Exception $e) {
            return back()->with('error', 'Error!');
        }

        return back()->with('success', 'Congrats!');
    }

    public function download(Torrent $torrent)
    {
        try {
            $torrentFile = Storage::get("public/torrents/{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            return 'Error, you requested an unavailable .torrent file.';
        }

        $decoder = new BdecodingService();
        $encoder = new BencodingService();
        $decodedTorrent = $decoder->decode($torrentFile);
        $passkey = auth()->user()->passkey;
        $decodedTorrent['announce'] = route('announce') . '?passkey=' . $passkey;
        $filePath = "public/torrents/{$torrent->id}-55.torrent";
        Storage::put($filePath, $encoder->encode($decodedTorrent));
        $url = Storage::url($filePath);
        $path = public_path($url);
        return response()->download($path, $torrent->name . '.torrent', ['content-type' => 'application/x-bittorrent']);
    }
}
