<?php

declare(strict_types=1);

namespace App\Services\IMDb;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Psr\Log\LoggerInterface;

class IMDBImagesManager
{
    /**
     * @var IMDBManager
     */
    private $IMDBManager;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(IMDBManager $IMDBManager, Client $client, FilesystemManager $filesystemManager, LoggerInterface $logger)
    {
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
                $result = $this->client->request('GET', $url);
            } catch (GuzzleException $exception) {
                $this->logger->error(sprintf('"%s" - %s - %s', $url, $exception->getCode(), $exception->getMessage()));

                return;
            }

            if (200 === $result->getStatusCode()) {
                $poster = (string) $result->getBody();
            }
        }

        if ('' !== $poster) {
            $imdbImagesDisk->put($posterFileName, $poster);
        }
    }
}
