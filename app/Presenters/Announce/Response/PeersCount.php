<?php

declare(strict_types=1);

namespace App\Presenters\Announce\Response;

use InvalidArgumentException;

final class PeersCount
{
    private int $seedersCount;
    private int $leechersCount;

    public function __construct(int $seedersCount, int $leechersCount)
    {
        if ($seedersCount < 0) {
            throw new InvalidArgumentException('A torrent cannot have less than zero seeders.');
        }

        if ($leechersCount < 0) {
            throw new InvalidArgumentException('A torrent cannot have less than zero leechers.');
        }

        $this->seedersCount = $seedersCount;
        $this->leechersCount = $leechersCount;
    }

    public function getSeedersCount(): int
    {
        return $this->seedersCount;
    }

    public function getLeechersCount(): int
    {
        return $this->leechersCount;
    }
}
