<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Generator;
use App\Presenters\Ip;
use App\Services\IpManager;
use PHPUnit\Framework\TestCase;
use App\Exceptions\InvalidIpException;

class IpManagerTest extends TestCase
{
    /**
     * @dataProvider ipV4WithoutPortDataProvider
     */
    public function testIsIpV4Method(string $ip, bool $valid): void
    {
        $ipManager = new IpManager();

        if ($valid) {
            $this->assertTrue($ipManager->isV4($ip));
        } else {
            $this->assertFalse($ipManager->isV4($ip));
        }
    }

    /**
     * @dataProvider ipV6WithoutPortDataProvider
     */
    public function testIsIpV6Method(string $ip, bool $valid): void
    {
        $ipManager = new IpManager();

        if ($valid) {
            $this->assertTrue($ipManager->isV6($ip));
        } else {
            $this->assertFalse($ipManager->isV6($ip));
        }
    }

    /**
     * @dataProvider validStringConversionV4DataProvider
     */
    public function testStringConversionToIpV4(string $ip, ?int $port, string $expectedIp, ?int $expectedPort): void
    {
        $ipManager = new IpManager();

        $ipObject = $ipManager->convertV4StringToIp($ip, $port);

        $this->assertInstanceOf(Ip::class, $ipObject);

        $this->assertSame($expectedIp, $ipObject->getIp());
        $this->assertSame($expectedPort, $ipObject->getPort());
        $this->assertTrue($ipObject->isV4());
        $this->assertFalse($ipObject->isV6());
    }

    /**
     * @dataProvider validStringConversionV6DataProvider
     */
    public function testStringConversionToIpV6(string $ip, ?int $port, string $expectedIp, ?int $expectedPort): void
    {
        $ipManager = new IpManager();

        $ipObject = $ipManager->convertV6StringToIp($ip, $port);

        $this->assertInstanceOf(Ip::class, $ipObject);

        $this->assertSame($expectedIp, $ipObject->getIp());
        $this->assertSame($expectedPort, $ipObject->getPort());
        $this->assertFalse($ipObject->isV4());
        $this->assertTrue($ipObject->isV6());
    }

    /**
     * @dataProvider portsDataProvider
     */
    public function testIsPortValidMethod(int $port, bool $isValid)
    {
        $ipManager = new IpManager();

        if ($isValid) {
            $this->assertTrue($ipManager->isPortValid($port));
        } else {
            $this->assertFalse($ipManager->isPortValid($port));
        }
    }

    /**
     * @dataProvider validIpsDataProvider
     */
    public function testMakeMethodReturnsValidIpObject(string $ip, ?int $port, string $type): void
    {
        $ipManager = new IpManager();

        $ipObject = $ipManager->make($ip, $port);

        $this->assertInstanceOf(Ip::class, $ipObject);

        if ('ipV4' === $type) {
            $this->assertTrue($ipObject->isV4());
            $this->assertFalse($ipObject->isV6());
        } elseif ('ipV6' === $type) {
            $this->assertFalse($ipObject->isV4());
            $this->assertTrue($ipObject->isV6());
        }

        $this->assertSame($ip, $ipObject->getIp());
        $this->assertSame($port, $ipObject->getPort());
    }

    public function testMakeMethodFailsIfNoValidIpProvided(): void
    {
        $this->expectException(InvalidIpException::class);

        $ipManager = new IpManager();

        $ipManager->make('random-value', null);
    }

    public function testMakeIpV4FailsIfNoValidIpProvided(): void
    {
        $this->expectException(InvalidIpException::class);

        $ipManager = new IpManager();

        $ipManager->makeIpV4('random-value', null);
    }

    public function testMakeIpV6FailsIfNoValidIpProvided(): void
    {
        $this->expectException(InvalidIpException::class);

        $ipManager = new IpManager();

        $ipManager->makeIpV6('random-value', null);
    }

    public function testConvertV4StringToIpFailsIfNoValidIpProvided(): void
    {
        $this->expectException(InvalidIpException::class);

        $ipManager = new IpManager();

        $ipManager->convertV4StringToIp('random-value', null);
    }

    public function testConvertV6StringToIpFailsIfNoValidIpProvided(): void
    {
        $this->expectException(InvalidIpException::class);

        $ipManager = new IpManager();

        $ipManager->convertV6StringToIp('random-value', null);
    }

    public function ipV4WithoutPortDataProvider(): Generator
    {
        yield ['127.0.0.1', true];
        yield ['invalid.ip', false];
        yield ['134.241.14.186', true];
    }

    public function ipV6WithoutPortDataProvider(): Generator
    {
        yield ['18b7:d268:1dc2:7d71:d2bf:daf9:f149:abce', true];
        yield ['invalid:string', false];
        yield ['::1', true];
    }

    public function validStringConversionV4DataProvider(): Generator
    {
        yield 'only IP' => ['127.0.0.1', null, '127.0.0.1', null];
        yield 'IP and port separated' => ['127.0.0.1', 300, '127.0.0.1', 300];
        yield 'IP with port' => ['127.0.0.1:400', null, '127.0.0.1', 400];
        yield 'IP with port and port provided separately' => ['127.0.0.1:400', 500, '127.0.0.1', 400];
    }

    public function validStringConversionV6DataProvider(): Generator
    {
        yield 'only IP' => ['1cc8:8438:4db:f8a3:274:da90:4ac6:f42a', null, '1cc8:8438:4db:f8a3:274:da90:4ac6:f42a', null];
        yield 'IP and port separated' => ['8134:18e5:e60e:809e:5dee:946b:d7:3e5a', 300, '8134:18e5:e60e:809e:5dee:946b:d7:3e5a', 300];
        yield 'IP with port' => ['[c403:8881:2b2f:dbd5:9e3d:98ea:43d:b152]:400', null, 'c403:8881:2b2f:dbd5:9e3d:98ea:43d:b152', 400];
        yield 'IP with port and port provided separately' => ['[f035:af94:d545:2b1e:656e:b920:6374:3124]:400', 500, 'f035:af94:d545:2b1e:656e:b920:6374:3124', 400];
    }

    public function portsDataProvider(): Generator
    {
        yield 'first valid port' => [1, true];
        yield 'last valid port' => [65535, true];
        yield 'first invalid port' => [65536, false];
        yield 'zero invalid port' => [0, false];
        yield 'negative invalid port' => [-50, false];
    }

    public function validIpsDataProvider(): Generator
    {
        yield 'PIv4 without port' => ['102.159.100.70', null, 'ipV4'];
        yield 'PIv4 with port' => ['220.188.235.114', 20, 'ipV4'];
        yield 'PIv6 without port' => ['2b3e:7ffa:d68b:1b89:5f15:3965:d433:579f', null, 'ipV6'];
        yield 'PIv6 with port' => ['b3ca:54d5:d0bf:9dd:785c:a36f:e975:3873', 50, 'ipV6'];
    }
}
