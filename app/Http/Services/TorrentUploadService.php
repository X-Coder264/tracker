<?php

declare(strict_types=1);

namespace App\Http\Services;

use Exception;
use Illuminate\Http\Request;
use App\Http\Models\Torrent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TorrentUploadService
{
    /**
     * @var BencodingService
     */
    protected $encoder;

    /**
     * @var BdecodingService
     */
    protected $decoder;

    /**
     * @var TorrentInfoService
     */
    protected $torrentInfoService;

    /**
     * @param BencodingService $encoder
     * @param BdecodingService $decoder
     * @param TorrentInfoService $torrentInfoService
     */
    public function __construct(
        BencodingService $encoder,
        BdecodingService $decoder,
        TorrentInfoService $torrentInfoService
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->torrentInfoService = $torrentInfoService;
    }

    /**
     * @param Request $request
     * @return Torrent
     * @throws Exception
     */
    public function upload(Request $request): Torrent
    {
        $torrentFile = $request->file('torrent');
        $torrentFilePath = $torrentFile->getRealPath();
        try {
            $torrentContent = File::get($torrentFilePath);
        } catch (Exception $e) {
            throw new Exception('The file could not be read.');
        }

        $decodedTorrent = $this->decoder->decode($torrentContent);

        $bytes = random_bytes(64);
        // add entropy to randomize info_hash in order to prevent peer leaking attacks
        $decodedTorrent['info']['entropy'] = bin2hex($bytes);

        // the torrent must be private
        $decodedTorrent['info']['private'] = 1;

        $torrentSize = $this->torrentInfoService->getTorrentSize($decodedTorrent['info']);

        // TODO: add support for multiple announce URLs
        $decodedTorrent['announce'] = route('announce');

        $torrent = new Torrent();
        $torrent->name = $request->input('name');
        $torrent->size = $torrentSize;
        $torrent->description = $request->input('description');
        $torrent->uploader_id = auth()->id();
        $torrent->infoHash = sha1($this->encoder->encode($decodedTorrent['info']));
        if (true === $torrent->save()) {
            $stored = Storage::disk('public')->put(
                "/torrents/{$torrent->id}.torrent",
                $this->encoder->encode($decodedTorrent)
            );
            if (false !== $stored) {
                return $torrent;
            } else {
                $torrent->delete();
                throw new Exception();
            }
        } else {
            throw new Exception();
        }
    }
}
