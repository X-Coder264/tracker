<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters\Announce;

use App\Presenters\Announce\IpAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class IpAddressTest extends TestCase
{
    /**
     * @dataProvider validIpAddressDataProvider
     */
    public function testIpAddressInstantiation(string $ip, bool $isIPv6): void
    {
        $ipAddress = new IpAddress($ip);
        $this->assertSame($ip, $ipAddress->getIp());
        $this->assertSame($isIPv6, $ipAddress->isIPv6());
    }

    /**
     * @dataProvider invalidIpAddressDataProvider
     */
    public function testInvalidIpAddressInstantiationThrowsAnException(string $ip): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(sprintf('Invalid IP address given - "%s"', $ip)));
        new IpAddress($ip);
    }

    public function validIpAddressDataProvider(): iterable
    {
        yield ['95.152.44.55', false];
        yield ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2', true];
        yield ['2001:3452:4952:2837::', true];
        yield ['FE80::0202:B3FF:FE1E:8329', true];
        yield ['1200:0000:AB00:1234:0000:2552:7777:1313', true];
        yield ['21DA:D3:0:2F3B:2AA:FF:FE28:9C5A', true];
    }

    public function invalidIpAddressDataProvider(): iterable
    {
        yield ['95.152.44.555'];
        yield ['95.152.44.'];
        yield ['95.152.44'];
        yield ['95.152'];
        yield ['95.'];
        yield ['95'];
        yield ['1200::AB00:1234::2552:7777:1313'];
        yield ['2001:db8:0:1'];
        yield ['1200:0000:AB00:1234:O000:2552:7777:1313'];
    }
}
