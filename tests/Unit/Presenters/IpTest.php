<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters;

use Generator;
use App\Presenters\Ip;
use PHPUnit\Framework\TestCase;

class IpTest extends TestCase
{
    /**
     * @dataProvider ipDataProvider
     */
    public function testGetters(string $ip, ?int $port, bool $isIpV4)
    {
        $ipObject = new Ip($ip, $port, $isIpV4);

        $this->assertSame($ip, $ipObject->getIp());
        $this->assertSame($port, $ipObject->getPort());

        if ($isIpV4) {
            $this->assertTrue($ipObject->isV4());
            $this->assertFalse($ipObject->isV6());
        } else {
            $this->assertFalse($ipObject->isV4());
            $this->assertTrue($ipObject->isV6());
        }

        if (null === $port) {
            $this->assertFalse($ipObject->hasPort());
        } else {
            $this->assertTrue($ipObject->hasPort());
        }
    }

    public function ipDataProvider(): Generator
    {
        yield 'only IPv4' => ['random-ip', null, true];
        yield 'IPv4 with port' => ['random-ip', 201, true];
        yield 'only IPv6' => ['random-ip', 202, false];
        yield 'IPv6 with port' => ['random-ip', 203, false];
    }
}
