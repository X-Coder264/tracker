<?php

declare(strict_types=1);

namespace App\Services\IMDb;

use InvalidArgumentException;

class IMDBLinkParser
{
    /**
     * @throws InvalidArgumentException
     */
    public function getId(string $imdbUrl): string
    {
        if ('' === $imdbUrl) {
            $this->throwInvalidArgumentException($imdbUrl);
        }

        $matches = [];
        $matched = preg_match('~((?:tt\d{6,})|(?:itle\?\d{6,}))~', $imdbUrl, $matches);

        if (false === $matched || 0 === $matched) {
            $this->throwInvalidArgumentException($imdbUrl);
        }

        return preg_replace('~[\D]~', '', $matches[0]);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function throwInvalidArgumentException(string $imdbUrl): void
    {
        throw new InvalidArgumentException(sprintf('Invalid IMDB URL ("%s") given', $imdbUrl));
    }
}
