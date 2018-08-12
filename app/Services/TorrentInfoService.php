<?php

declare(strict_types=1);

namespace App\Services;

use Imdb\Title;
use App\Models\Torrent;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

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
     * @var IMDBManager
     */
    private $IMDBManager;

    /**
     * @param SizeFormatter     $sizeFormatter
     * @param Bdecoder          $bdecoder
     * @param CacheManager      $cacheManager
     * @param FilesystemManager $filesystemManager
     * @param IMDBManager       $IMDBManager
     */
    public function __construct(
        SizeFormatter $sizeFormatter,
        Bdecoder $bdecoder,
        CacheManager $cacheManager,
        FilesystemManager $filesystemManager,
        IMDBManager $IMDBManager
    ) {
        $this->sizeFormatter = $sizeFormatter;
        $this->bdecoder = $bdecoder;
        $this->cacheManager = $cacheManager;
        $this->filesystemManager = $filesystemManager;
        $this->IMDBManager = $IMDBManager;
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
                $torrentFile = $this->filesystemManager->disk('torrents')->get("{$torrent->id}.torrent");
                $decodedTorrent = $this->bdecoder->decode($torrentFile);

                return $this->getTorrentFileNamesAndSizesFromTorrentInfoDict($decodedTorrent['info']);
            }
        );
    }

    /**
     * @param Torrent $torrent
     *
     * @return Title|null
     */
    public function getTorrentIMDBData(Torrent $torrent): ?Title
    {
        $imdbData = null;

        if (! empty($torrent->imdb_id)) {
            $imdbData = $this->IMDBManager->getTitleFromIMDBId($torrent->imdb_id);
        }

        return $imdbData;
    }
}
