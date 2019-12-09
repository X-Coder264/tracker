<?php

declare(strict_types=1);

namespace App\Services\IMDb;

use Illuminate\Contracts\Cache\Repository;
use Imdb\Config;
use Imdb\Title;

class IMDBManager
{
    private IMDBLinkParser $IMDBLinkParser;
    private Repository $cache;

    public function __construct(IMDBLinkParser $IMDBLinkParser, Repository $cache)
    {
        $this->IMDBLinkParser = $IMDBLinkParser;
        $this->cache = $cache;
    }

    public function getTitleFromFullURL(string $imdbUrl): Title
    {
        $imdbId = $this->IMDBLinkParser->getId($imdbUrl);

        return new Title($imdbId, $this->getIMDBConfig(), null, $this->cache);
    }

    public function getTitleFromIMDBId(string $imdbId): Title
    {
        return new Title($imdbId, $this->getIMDBConfig(), null, $this->cache);
    }

    public function getIMDBIdFromFullURL(string $imdbUrl): string
    {
        return $this->IMDBLinkParser->getId($imdbUrl);
    }

    public function getPosterURLFromIMDBId(string $imdbId): ?string
    {
        $title = $this->getTitleFromIMDBId($imdbId);
        $posterURL = $title->photo(true);

        if (empty($posterURL)) {
            return null;
        }

        return $posterURL;
    }

    private function getIMDBConfig(): Config
    {
        $config = new Config();
        // number of minutes to cache IMDB data (Laravel's cache stores take minutes, not seconds!)
        $config->cache_expire = 60 * 24;

        return $config;
    }
}
