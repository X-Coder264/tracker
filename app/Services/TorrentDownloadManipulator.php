<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Str;

final class TorrentDownloadManipulator
{
    private UrlGenerator $urlGenerator;
    private Factory $filesystemFactory;
    private Bencoder $bencoder;
    private Bdecoder $bdecoder;

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
