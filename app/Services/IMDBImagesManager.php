<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

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
     * @param IMDBManager       $IMDBManager
     * @param Client            $client
     * @param FilesystemManager $filesystemManager
     */
    public function __construct(IMDBManager $IMDBManager, Client $client, FilesystemManager $filesystemManager)
    {
        $this->IMDBManager = $IMDBManager;
        $this->client = $client;
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * @param string $imdbId
     */
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
