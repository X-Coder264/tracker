<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionClass;
use App\Http\Models\Peer;
use App\Http\Models\PeerIP;
use Illuminate\Support\Collection;
use App\Http\Services\AnnounceService;
use App\Http\Services\BencodingService;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AnnounceServiceTest extends TestCase
{
    public function testIPv4AddressValidation()
    {
        $announceService = new AnnounceService(new BencodingService());
        $reflectionClass = new ReflectionClass(AnnounceService::class);
        $method = $reflectionClass->getMethod('validateIPv4Address');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($announceService, ['95.152.44.55']));
        $this->assertFalse($method->invokeArgs($announceService, ['95.152.44.555']));
        $this->assertFalse($method->invokeArgs($announceService, ['95.152.44.']));
        $this->assertFalse($method->invokeArgs($announceService, ['95.152.44']));
        $this->assertFalse($method->invokeArgs($announceService, ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2']));
    }

    public function testIPv6AddressValidation()
    {
        $announceService = new AnnounceService(new BencodingService());
        $reflectionClass = new ReflectionClass(AnnounceService::class);
        $method = $reflectionClass->getMethod('validateIPv6Address');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($announceService, ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2']));
        $this->assertTrue($method->invokeArgs($announceService, ['2001:3452:4952:2837::']));
        $this->assertTrue($method->invokeArgs($announceService, ['FE80::0202:B3FF:FE1E:8329']));
        $this->assertTrue($method->invokeArgs($announceService, ['1200:0000:AB00:1234:0000:2552:7777:1313']));
        $this->assertTrue($method->invokeArgs($announceService, ['21DA:D3:0:2F3B:2AA:FF:FE28:9C5A']));
        $this->assertFalse($method->invokeArgs($announceService, ['1200::AB00:1234::2552:7777:1313']));
        $this->assertFalse($method->invokeArgs($announceService, ['[2001:db8:0:1]:80']));
        $this->assertFalse($method->invokeArgs($announceService, ['1200:0000:AB00:1234:O000:2552:7777:1313']));
    }

    public function testErrorResponseWithStringParameter()
    {
        /* @var MockObject|BencodingService $encoder */
        $encoder = $this->getMockBuilder(BencodingService::class)
            ->setMethods(['encode'])
            ->getMock();
        $error = 'Error xyz.';
        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $error]))
            ->willReturn($returnValue);

        $announceService = new AnnounceService($encoder);
        $reflectionClass = new ReflectionClass(AnnounceService::class);
        $method = $reflectionClass->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $this->assertSame($returnValue, $method->invokeArgs($announceService, [$error]));
    }

    public function testErrorResponseWithArrayParameter()
    {
        /* @var MockObject|BencodingService $encoder */
        $encoder = $this->getMockBuilder(BencodingService::class)
            ->setMethods(['encode'])
            ->getMock();
        $error = ['Error X.', 'Error Y.'];
        $errorMessage = '';
        foreach ($error as $message) {
            $errorMessage .= $message . ' ';
        }
        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $errorMessage]))
            ->willReturn($returnValue);

        $announceService = new AnnounceService($encoder);
        $reflectionClass = new ReflectionClass(AnnounceService::class);
        $method = $reflectionClass->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $this->assertSame($returnValue, $method->invokeArgs($announceService, [$error]));
    }

    public function testCompactResponse()
    {
        $peer = factory(Peer::class)->make(['torrent_id' => 1, 'user_id' => 1, 'seeder' => true]);

        $IPs = new Collection([
            factory(PeerIP::class)->make(['peerID' => $peer, 'IP' => '95.152.44.55', 'isIPv6' => false, 'port' => 55555]),
            factory(PeerIP::class)->make(['peerID' => $peer, 'IP' => '2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2', 'isIPv6' => true, 'port' => 60000]),
        ]);

        $peer->setRelation('IPs', $IPs);

        $peers = new EloquentCollection([$peer]);

        $encoder = $this->getMockBuilder(BencodingService::class)
            ->setMethods(['encode'])
            ->getMock();

        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(
                [
                    'interval' => 2400,
                    'min interval' => 60,
                    'complete' => 1,
                    'incomplete' => 0,
                    'peers' => inet_pton('95.152.44.55') . pack('n*', 55555),
                    'peers6' => inet_pton('2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2') . pack('n*', 60000),
                ]
            ))
            ->willReturn($returnValue);

        $announceService = $this->getMockBuilder(AnnounceService::class)
            ->setConstructorArgs([$encoder])
            ->setMethods(['getPeers'])
            ->getMock();
        $announceService->expects($this->once())
            ->method('getPeers')
            ->willReturn($peers);

        $reflectionClass = new ReflectionClass(AnnounceService::class);
        $reflectionMethod = $reflectionClass->getMethod('compactResponse');
        $reflectionMethod->setAccessible(true);
        $this->assertSame($returnValue, $reflectionMethod->invoke($announceService));
    }
}