<?php

declare(strict_types=1);

namespace App\Http\Services;

use Exception;
use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

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
     * @param BencodingService   $encoder
     * @param BdecodingService   $decoder
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
     *
     * @throws Exception
     *
     * @return Torrent
     */
    public function upload(Request $request): Torrent
    {
        $torrentFile = $request->file('torrent');
        $torrentFilePath = $torrentFile->getRealPath();

        try {
            $torrentContent = File::get($torrentFilePath);
        } catch (FileNotFoundException $e) {
            throw new Exception('The file could not be read.');
        }

        $decodedTorrent = $this->decoder->decode($torrentContent);

        // the torrent must be private
        $decodedTorrent['info']['private'] = 1;

        $torrentSize = $this->torrentInfoService->getTorrentSize($decodedTorrent['info']);

        // TODO: add support for multiple announce URLs
        $decodedTorrent['announce'] = route('announce');

        do {
            $bytes = random_bytes(64);
            // add entropy to randomize info_hash in order to prevent peer leaking attacks
            // we are recalculating the entropy until we get an unique info_hash
            $decodedTorrent['info']['entropy'] = bin2hex($bytes);

            $infoHash = $this->getTorrentInfoHash($decodedTorrent['info']);

            $torrent = Torrent::where('infoHash', '=', $infoHash)->select('infoHash')->first();
        } while (null !== $torrent);

        $torrent = new Torrent();
        $torrent->name = $request->input('name');
        $torrent->size = $torrentSize;
        $torrent->description = $request->input('description');
        $torrent->uploader_id = Auth::id();
        $torrent->infoHash = $infoHash;
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

    /**
     * @param array $torrentInfoDictionary
     *
     * @return string
     */
    protected function getTorrentInfoHash(array $torrentInfoDictionary): string
    {
        return sha1($this->encoder->encode($torrentInfoDictionary));
    }
}
