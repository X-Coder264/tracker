<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters\Announce\Response;

use App\Presenters\Announce\Response\Peer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PeerTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testCreatingAPeerWithAnInvalidPortThrowsAnException(int $port): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(sprintf('The given port %d is not valid.', $port)));

        new Peer('192.168.1.1', false, $port, 'abcdef');
    }

    public function dataProvider(): iterable
    {
        yield [-1];
        yield [0];
        yield [65536];
    }
}
