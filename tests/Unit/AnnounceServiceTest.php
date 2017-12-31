<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionClass;
use App\Http\Services\AnnounceService;
use App\Http\Services\BencodingService;
use PHPUnit\Framework\MockObject\MockObject;

class AnnounceServiceTest extends TestCase
{
    public function testIPv4AddressValidation()
    {
        $announceService = new AnnounceService(new BencodingService());
        $reflector = new ReflectionClass(AnnounceService::class);
        $method = $reflector->getMethod('validateIPv4Address');
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
        $reflector = new ReflectionClass(AnnounceService::class);
        $method = $reflector->getMethod('validateIPv6Address');
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
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $error]))
            ->willReturn('something');

        $announceService = new AnnounceService($encoder);
        $reflector = new ReflectionClass(AnnounceService::class);
        $method = $reflector->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $method->invokeArgs($announceService, [$error]);
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
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $errorMessage]))
            ->willReturn('something');

        $announceService = new AnnounceService($encoder);
        $reflector = new ReflectionClass(AnnounceService::class);
        $method = $reflector->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $method->invokeArgs($announceService, [$error]);
    }
}
