<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\FileNotWritableException;

class TorrentUploadService
{
    /**
     * @var Bencoder
     */
    protected $encoder;

    /**
     * @var Bdecoder
     */
    protected $decoder;

    /**
     * @var TorrentInfoService
     */
    protected $torrentInfoService;

    /**
     * @param Bencoder           $encoder
     * @param Bdecoder           $decoder
     * @param TorrentInfoService $torrentInfoService
     */
    public function __construct(
        Bencoder $encoder,
        Bdecoder $decoder,
        TorrentInfoService $torrentInfoService
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->torrentInfoService = $torrentInfoService;
    }

    /**
     * @param Request $request
     *
     * @throws FileNotWritableException
     *
     * @return Torrent
     */
    public function upload(Request $request): Torrent
    {
        $torrentFile = $request->file('torrent');
        $torrentFilePath = $torrentFile->getRealPath();

        $torrentContent = File::get($torrentFilePath);
        $decodedTorrent = $this->decoder->decode($torrentContent);

        // the torrent must be private
        $decodedTorrent['info']['private'] = 1;

        $torrentSize = $this->torrentInfoService->getTorrentSize($decodedTorrent['info']);

        // TODO: add support for multiple announce URLs
        $decodedTorrent['announce'] = route('announce');

        do {
            // add entropy to randomize info_hash in order to prevent peer leaking attacks
            // we are recalculating the entropy until we get an unique info_hash
            $decodedTorrent['info']['entropy'] = bin2hex(random_bytes(64));

            $infoHash = $this->getTorrentInfoHash($decodedTorrent['info']);

            $torrent = Torrent::where('infoHash', '=', $infoHash)->select('infoHash')->first();
        } while (null !== $torrent);

        $torrent = new Torrent();
        $torrent->name = $request->input('name');
        $torrent->size = $torrentSize;
        $torrent->description = $request->input('description');
        $torrent->uploader_id = Auth::id();
        $torrent->infoHash = $infoHash;
        $torrent->save();

        $stored = Storage::disk('public')->put(
            "/torrents/{$torrent->id}.torrent",
            $this->encoder->encode($decodedTorrent)
        );

        if (false !== $stored) {
            return $torrent;
        }

        $torrent->delete();

        throw new FileNotWritableException(__('messages.file-not-writable-exception.error-message'));
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
