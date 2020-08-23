<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters\Announce;

use App\Presenters\Announce\Torrent;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TorrentTest extends TestCase
{
    public function testCreatingATorrentWithSeedersCountLowerThanZeroThrowsAnException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('A torrent cannot have less than zero seeders.'));

        new Torrent(1, -1, 0, 'foo', 2);
    }

    public function testCreatingATorrentWithLeechersCountLowerThanZeroThrowsAnException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('A torrent cannot have less than zero leechers.'));

        new Torrent(1, 0, -1, 'foo', 2);
    }

    public function testCreatingATorrentWithInvalidVersionThrowsAnException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Only v1 (BEP 3) and v2 (BEP 52) exist. v10 does not exist.'));

        new Torrent(1, 0, 0, 'foo', 10);
    }
}
