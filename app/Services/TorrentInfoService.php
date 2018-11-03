<?php

declare(strict_types=1);

namespace App\Services;

use Generator;
use Imdb\Title;
use App\Models\Torrent;
use App\Enumerations\Cache;
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

    public function getTorrentSize(array $torrentInfoDict): int
    {
        $size = 0;
        foreach ($this->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict) as $path => $fileSize) {
            $size += $fileSize;
        }

        return $size;
    }

    public function getTorrentFileNamesAndSizesFromTorrentInfoDict(array $torrentInfoDict): array
    {
        if (false === $this->isV2Torrent($torrentInfoDict)) {
            $data = $this->getV1TorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict);
        } else {
            $data = $this->getV2TorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict);
        }

        return iterator_to_array($data);
    }

    private function getV1TorrentFileNamesAndSizesFromTorrentInfoDict(array $torrentInfoDict): Generator
    {
        if (isset($torrentInfoDict['files'])) {
            // multiple file mode
            foreach ($torrentInfoDict['files'] as $file) {
                yield implode('/', $file['path']) => $file['length'];
            }

            return;
        }

        // single file mode
        yield $torrentInfoDict['name'] => $torrentInfoDict['length'];
    }

    private function getV2TorrentFileNamesAndSizesFromTorrentInfoDict(array $torrentInfoDict): Generator
    {
        yield from $this->v2FileSizeExtract($torrentInfoDict['file tree']);
    }

    private function v2FileSizeExtract(array $files, string $path = null): Generator
    {
        foreach ($files as $name => $file) {
            if (false === is_array($file)) {
                return;
            }

            if (null !== $path) {
                $name = $path . '/' . $name;
            }

            if (isset($file['']) && isset($file['']['length'])) {
                yield $name => $file['']['length'];
            }

            yield from $this->v2FileSizeExtract($file, $name);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function getTorrentFileNamesAndSizes(Torrent $torrent): array
    {
        $key = sprintf('torrent.%s.files', $torrent->id);

        return $this->cacheManager->remember(
            $key,
            Cache::ONE_MONTH,
            function () use ($torrent): array {
                $torrentFile = $this->filesystemManager->disk('torrents')->get("{$torrent->id}.torrent");
                $decodedTorrent = $this->bdecoder->decode($torrentFile);

                return $this->getTorrentFileNamesAndSizesFromTorrentInfoDict($decodedTorrent['info']);
            }
        );
    }

    public function isV1Torrent(array $torrentInfoDict): bool
    {
        return ! empty($torrentInfoDict['files']) || ! empty($torrentInfoDict['length']);
    }

    public function isV2Torrent(array $torrentInfoDict): bool
    {
        if (! empty($torrentInfoDict['meta version']) && 2 === $torrentInfoDict['meta version']) {
            return true;
        }

        return false;
    }

    public function isHybridTorrent(array $torrentInfoDict): bool
    {
        return $this->isV2Torrent($torrentInfoDict) && (! empty($torrentInfoDict['files']) || ! empty($torrentInfoDict['length']));
    }

    public function getTorrentIMDBData(Torrent $torrent): ?Title
    {
        $imdbData = null;

        if (! empty($torrent->imdb_id)) {
            $imdbData = $this->IMDBManager->getTitleFromIMDBId($torrent->imdb_id);
        }

        return $imdbData;
    }
}
