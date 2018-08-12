<?php

declare(strict_types=1);

namespace App\Services;

use Imdb\Title;
use Imdb\Config;
use Illuminate\Cache\CacheManager;

class IMDBManager
{
    /**
     * @var IMDBLinkParser
     */
    private $IMDBLinkParser;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @param IMDBLinkParser $IMDBLinkParser
     * @param CacheManager   $cacheManager
     */
    public function __construct(IMDBLinkParser $IMDBLinkParser, CacheManager $cacheManager)
    {
        $this->IMDBLinkParser = $IMDBLinkParser;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param string $imdbUrl
     *
     * @return Title
     */
    public function getTitleFromFullURL(string $imdbUrl): Title
    {
        $imdbId = $this->IMDBLinkParser->getId($imdbUrl);

        return new Title($imdbId, $this->getIMDBConfig(), null, $this->cacheManager->store());
    }

    /**
     * @param string $imdbId
     *
     * @return Title
     */
    public function getTitleFromIMDBId(string $imdbId): Title
    {
        return new Title($imdbId, $this->getIMDBConfig(), null, $this->cacheManager->store());
    }

    /**
     * @param string $imdbUrl
     *
     * @return string
     */
    public function getIMDBIdFromFullURL(string $imdbUrl): string
    {
        return $this->IMDBLinkParser->getId($imdbUrl);
    }

    /**
     * @param string $imdbId
     *
     * @return null|string
     */
    public function getPosterURLFromIMDBId(string $imdbId): ?string
    {
        $title = $this->getTitleFromIMDBId($imdbId);
        $posterURL = $title->photo(true);

        if (empty($posterURL)) {
            return null;
        }

        return $posterURL;
    }

    /**
     * @return Config
     */
    private function getIMDBConfig(): Config
    {
        $config = new Config();
        // number of minutes to cache IMDB data (Laravel's cache stores take minutes, not seconds!)
        $config->cache_expire = 60 * 24;

        return $config;
    }
}
