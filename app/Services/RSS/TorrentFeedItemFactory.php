<?php

declare(strict_types=1);

namespace App\Services\RSS;

use App\Models\Torrent;
use App\Presenters\RSS\FeedItem;
use Illuminate\Contracts\Routing\UrlGenerator;

class TorrentFeedItemFactory
{
    private UrlGenerator $urlGenerator;

    public function __construct(UrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function make(Torrent $torrent, string $passkey): FeedItem
    {
        return new FeedItem(
            $torrent->name,
            $this->urlGenerator->route('torrents.download', ['torrent' => $torrent, 'passkey' => $passkey]),
            $torrent->infoHashes->first()->info_hash,
            $torrent->created_at
        );
    }
}
