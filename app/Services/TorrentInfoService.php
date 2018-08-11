<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Torrent;
use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\FilesystemManager;
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
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    /**
     * @param SizeFormatter     $sizeFormatter
     * @param Bdecoder          $bdecoder
     * @param CacheManager      $cacheManager
     * @param FilesystemManager $filesystemManager
     */
    public function __construct(
        SizeFormatter $sizeFormatter,
        Bdecoder $bdecoder,
        CacheManager $cacheManager,
        FilesystemManager $filesystemManager
    ) {
        $this->sizeFormatter = $sizeFormatter;
        $this->bdecoder = $bdecoder;
        $this->cacheManager = $cacheManager;
        $this->filesystemManager = $filesystemManager;
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
        return $this->cacheManager->rememberForever(
            'torrent.' . $torrent->id . '.files',
            function () use ($torrent) {
                $torrentFile = $this->filesystemManager->disk('public')->get("torrents/{$torrent->id}.torrent");
                $decodedTorrent = $this->bdecoder->decode($torrentFile);

                return $this->getTorrentFileNamesAndSizesFromTorrentInfoDict($decodedTorrent['info']);
            }
        );
    }
}
