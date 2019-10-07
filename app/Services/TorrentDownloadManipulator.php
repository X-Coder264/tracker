<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

final class TorrentDownloadManipulator
{
    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Factory
     */
    private $filesystemFactory;

    /**
     * @var Bencoder
     */
    private $bencoder;

    /**
     * @var Bdecoder
     */
    private $bdecoder;

    public function __construct(
        UrlGenerator $urlGenerator,
        Factory $filesystemFactory,
        Bencoder $bencoder,
        Bdecoder $bdecoder
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->filesystemFactory = $filesystemFactory;
        $this->bencoder = $bencoder;
        $this->bdecoder = $bdecoder;
    }

    /**
     * @throws FileNotFoundException
     */
    public function getTorrentContent(int $torrentId, string $passkey): string
    {
        $torrentFile = $this->filesystemFactory->disk('torrents')->get("{$torrentId}.torrent");
        $decodedTorrent = $this->bdecoder->decode($torrentFile);

        $decodedTorrent['announce'] = $this->urlGenerator->route('announce', ['passkey' => $passkey]);

        return $this->bencoder->encode($decodedTorrent);
    }

    public function getTorrentName(string $originalName): string
    {
        // TODO: add support for adding a prefix (or suffix) to the name of the file
        return str_replace(['/', '\\'], '', $originalName . '.torrent');
    }

    public function getFallBackTorrentName(string $originalName): string
    {
        return str_replace('%', '', Str::ascii($this->getTorrentName($originalName)));
    }
}
