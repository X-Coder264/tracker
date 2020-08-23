<?php

declare(strict_types=1);

namespace App\Presenters\Announce;

use InvalidArgumentException;

final class Torrent
{
    private int $id;
    private int $seedersCount;
    private int $leechersCount;
    private string $slug;
    private int $version;

    public function __construct(int $id, int $seedersCount, int $leechersCount, string $slug, int $version)
    {
        $this->id = $id;

        if ($seedersCount < 0) {
            throw new InvalidArgumentException('A torrent cannot have less than zero seeders.');
        }

        if ($leechersCount < 0) {
            throw new InvalidArgumentException('A torrent cannot have less than zero leechers.');
        }

        $this->seedersCount = $seedersCount;
        $this->leechersCount = $leechersCount;
        $this->slug = $slug;

        if (! in_array($version, [1, 2], true)) {
            throw new InvalidArgumentException(sprintf('Only v1 (BEP 3) and v2 (BEP 52) exist. v%d does not exist.', $version));
        }

        $this->version = $version;
    }

    public static function createFromSelfWithUpdatedSeedersAndLeechersCount(self $torrent, int $seedersCount, int $leechersCount): self
    {
        return new self($torrent->getId(), $seedersCount, $leechersCount, $torrent->getSlug(), $torrent->getVersion());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSeedersCount(): int
    {
        return $this->seedersCount;
    }

    public function getLeechersCount(): int
    {
        return $this->leechersCount;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
