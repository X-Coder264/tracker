<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Services\Bencoder;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\ConnectionInterface;

final class ScrapeManager
{
    private ConnectionInterface $connection;
    private ErrorResponseFactory $errorResponseFactory;
    private Translator $translator;
    private Bencoder $encoder;

    public function __construct(
        ConnectionInterface $connection,
        ErrorResponseFactory $errorResponseFactory,
        Translator $translator,
        Bencoder $encoder
    ) {
        $this->connection = $connection;
        $this->errorResponseFactory = $errorResponseFactory;
        $this->translator = $translator;
        $this->encoder = $encoder;
    }

    public function scrape(array $infoHashes): string
    {
        $response = [];

        foreach ($infoHashes as $infoHash) {
            $torrent = $this->connection
                ->table('torrents')
                ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
                ->where('info_hash', '=', bin2hex($infoHash))
                ->select(['torrents.id', 'seeders', 'leechers'])
                ->first();

            if (null === $torrent) {
                continue;
            }

            $snatchesCount = $this->connection
                ->table('snatches')
                ->where('torrent_id', '=', $torrent->id)
                ->where('left', '=', 0)
                ->count();

            $response['files'][$infoHash] = [
                'complete' => (int) $torrent->seeders,
                'incomplete' => (int) $torrent->leechers,
                'downloaded' => $snatchesCount,
            ];
        }

        if (empty($response['files'])) {
            return $this->errorResponseFactory->create($this->translator->get('messages.scrape.no_torrents'));
        }

        return $this->encoder->encode($response);
    }
}
