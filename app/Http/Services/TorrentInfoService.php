<?php

declare(strict_types=1);

namespace App\Http\Services;

use App\Http\Models\Torrent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class TorrentInfoService
{
    /**
     * @var SizeFormattingService
     */
    private $sizeFormattingService;

    /**
     * @var BdecodingService
     */
    private $bdecodingService;

    /**
     * @param SizeFormattingService $sizeFormattingService
     * @param BdecodingService $bdecodingService
     */
    public function __construct(SizeFormattingService $sizeFormattingService, BdecodingService $bdecodingService)
    {
        $this->sizeFormattingService = $sizeFormattingService;
        $this->bdecodingService = $bdecodingService;
    }

    /**
     * @param array $torrentInfoDict
     * @return int
     */
    public function getTorrentSize(array $torrentInfoDict)
    {
        // TODO: add return type hint
        $size = 0;
        if (isset($torrentInfoDict['files'])) {
            // multiple file mode
            foreach ($torrentInfoDict['files'] as $file) {
                $size += $file['length'];
            }
        } else {
            // single file mode
            $size = $torrentInfoDict['length'];
        }

        return $size;
    }

    /**
     * @param array $torrentInfoDict
     * @return array
     */
    public function getTorrentFileNamesAndSizesFromTorrentInfoDict(array $torrentInfoDict): array
    {
        $fileNamesAndSizes = [];

        if (isset($torrentInfoDict['files'])) {
            // multiple file mode
            foreach ($torrentInfoDict['files'] as $file) {
                $size = $this->sizeFormattingService->getFormattedSize($file['length']);
                $fileName = '';
                foreach ($file['path'] as $path) {
                    $fileName .= $path . '/';
                }

                $fileName = pathinfo($fileName, PATHINFO_BASENAME);
                $fileNamesAndSizes[] = [$fileName, $size];
            }
        } else {
            // single file mode
            $size = $this->sizeFormattingService->getFormattedSize($torrentInfoDict['length']);
            $fileName = $torrentInfoDict['name'];
            $fileNamesAndSizes[] = [$fileName, $size];
        }

        return $fileNamesAndSizes;
    }

    /**
     * @param Torrent $torrent
     * @return mixed
     */
    public function getTorrentFileNamesAndSizes(Torrent $torrent)
    {
        $torrentFileNamesAndSizes = Cache::rememberForever(
            'torrent' . $torrent->id . 'files',
            function () use ($torrent) {
                try {
                    $torrentFile = Storage::disk('public')->get("torrents/{$torrent->id}.torrent");
                } catch (FileNotFoundException $e) {
                    abort(404, 'You requested an unavailable .torrent file.');
                }
                $decodedTorrent = $this->bdecodingService->decode($torrentFile);
                return $this->getTorrentFileNamesAndSizesFromTorrentInfoDict($decodedTorrent['info']);
            }
        );

        return $torrentFileNamesAndSizes;
    }
}
