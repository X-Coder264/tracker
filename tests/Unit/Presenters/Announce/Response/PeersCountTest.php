<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters\Announce\Response;

use App\Presenters\Announce\Response\PeersCount;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PeersCountTest extends TestCase
{
    public function testCreatingAPeersCountObjectWithSeedersCountLowerThanZeroThrowsAnException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('A torrent cannot have less than zero seeders.'));

        new PeersCount(-1, 0);
    }

    public function testCreatingAPeersCountObjectWithLeechersCountLowerThanZeroThrowsAnException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('A torrent cannot have less than zero leechers.'));

        new PeersCount(0, -1);
    }
}
