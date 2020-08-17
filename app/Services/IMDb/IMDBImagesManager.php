<?php

declare(strict_types=1);

namespace App\Services\IMDb;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IMDBImagesManager
{
    private IMDBManager $IMDBManager;
    private HttpClientInterface $client;
    private FilesystemManager $filesystemManager;
    private LoggerInterface $logger;

    public function __construct(
        IMDBManager $IMDBManager,
        HttpClientInterface $client,
        FilesystemManager $filesystemManager,
        LoggerInterface $logger
    ) {
        $this->IMDBManager = $IMDBManager;
        $this->client = $client;
        $this->filesystemManager = $filesystemManager;
        $this->logger = $logger;
    }

    public function writePosterToDisk(string $imdbId): void
    {
        $url = $this->IMDBManager->getPosterURLFromIMDBId($imdbId);

        $poster = '';

        $imdbImagesDisk = $this->filesystemManager->disk('imdb-images');
        $posterFileName = $imdbId . '.jpg';

        if (null !== $url && true !== $imdbImagesDisk->exists($posterFileName)) {
            try {
                $response = $this->client->request('GET', $url, ['timeout' => 1.5]);
            } catch (TransportExceptionInterface $exception) {
                $this->logger->error(
                    'Imdb fetching error',
                    [
                        'exception' => $exception,
                        'extra' => [
                            'imdbId' => $imdbId,
                            'url' => $url,
                        ],
                    ]
                );

                return;
            }

            if (200 === $response->getStatusCode()) {
                $poster = $response->getContent();
            }
        }

        if ('' !== $poster) {
            $imdbImagesDisk->put($posterFileName, $poster);
        }
    }
}
