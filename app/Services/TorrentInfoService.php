<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Models\Torrent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class TorrentInfoService
{
    /**
     * @var SizeFormatter
     */
    private $sizeFormatter;

    /**
     * @var Bdecoder
     */
    private $bdecoder;

    /**
     * @param SizeFormatter $sizeFormatter
     * @param Bdecoder      $bdecoder
     */
    public function __construct(SizeFormatter $sizeFormatter, Bdecoder $bdecoder)
    {
        $this->sizeFormatter = $sizeFormatter;
        $this->bdecoder = $bdecoder;
    }

    /**
     * @param array $torrentInfoDict
     *
     * @return int
     */
    public function getTorrentSize(array $torrentInfoDict): int
    {
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
     *
     * @return array
     */
    public function getTorrentFileNamesAndSizesFromTorrentInfoDict(array $torrentInfoDict): array
    {
        $fileNamesAndSizes = [];

        if (isset($torrentInfoDict['files'])) {
            // multiple file mode
            foreach ($torrentInfoDict['files'] as $file) {
                $size = $this->sizeFormatter->getFormattedSize($file['length']);
                $fileName = '';
                foreach ($file['path'] as $path) {
                    $fileName .= $path . '/';
                }

                $fileName = pathinfo($fileName, PATHINFO_BASENAME);
                $fileNamesAndSizes[] = [$fileName, $size];
            }
        } else {
            // single file mode
            $size = $this->sizeFormatter->getFormattedSize($torrentInfoDict['length']);
            $fileName = $torrentInfoDict['name'];
            $fileNamesAndSizes[] = [$fileName, $size];
        }

        return $fileNamesAndSizes;
    }

    /**
     * @param Torrent $torrent
     *
     * @throws FileNotFoundException
     *
     * @return array
     */
    public function getTorrentFileNamesAndSizes(Torrent $torrent): array
    {
        return Cache::rememberForever(
            'torrent.' . $torrent->id . '.files',
            function () use ($torrent) {
                $torrentFile = Storage::disk('public')->get("torrents/{$torrent->id}.torrent");
                $decodedTorrent = $this->bdecoder->decode($torrentFile);

                return $this->getTorrentFileNamesAndSizesFromTorrentInfoDict($decodedTorrent['info']);
            }
        );
    }
}
