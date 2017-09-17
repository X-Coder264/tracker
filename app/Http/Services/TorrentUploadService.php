<?php

declare(strict_types=1);

namespace App\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Validator;
use Exception;
use App\Http\Models\Torrent;
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
     * @param BencodingService $encoder
     * @param BdecodingService $decoder
     */
    public function __construct(BencodingService $encoder, BdecodingService $decoder)
    {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
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

        $decodedTorrent['announce'] = route('announce');

        $torrent = new Torrent();
        $torrent->name = pathinfo($torrentFile->getClientOriginalName(), PATHINFO_FILENAME);
        $torrent->description = $request->input('description');
        $torrent->uploader_id = auth()->id();
        $torrent->infoHash = sha1($this->encoder->encode($decodedTorrent['info']));
        if (true === $torrent->save()) {
            $stored = Storage::put("public/torrents/{$torrent->id}.torrent", $this->encoder->encode($decodedTorrent));
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
