<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Services\Bencoder;
use App\Services\AnnounceManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class AnnounceManagerTest extends TestCase
{
    public function testIPv4AddressValidation()
    {
        $announceManager = new AnnounceManager(
            new Bencoder(),
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('validateIPv4Address');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($announceManager, ['95.152.44.55']));
        $this->assertFalse($method->invokeArgs($announceManager, ['95.152.44.555']));
        $this->assertFalse($method->invokeArgs($announceManager, ['95.152.44.']));
        $this->assertFalse($method->invokeArgs($announceManager, ['95.152.44']));
        $this->assertFalse($method->invokeArgs($announceManager, ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2']));
    }

    public function testIPv6AddressValidation()
    {
        $announceManager = new AnnounceManager(
            new Bencoder(),
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('validateIPv6Address');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($announceManager, ['2b63:1478:1ac5:37ef:4e8c:75df:14cd:93f2']));
        $this->assertTrue($method->invokeArgs($announceManager, ['2001:3452:4952:2837::']));
        $this->assertTrue($method->invokeArgs($announceManager, ['FE80::0202:B3FF:FE1E:8329']));
        $this->assertTrue($method->invokeArgs($announceManager, ['1200:0000:AB00:1234:0000:2552:7777:1313']));
        $this->assertTrue($method->invokeArgs($announceManager, ['21DA:D3:0:2F3B:2AA:FF:FE28:9C5A']));
        $this->assertFalse($method->invokeArgs($announceManager, ['1200::AB00:1234::2552:7777:1313']));
        $this->assertFalse($method->invokeArgs($announceManager, ['[2001:db8:0:1]:80']));
        $this->assertFalse($method->invokeArgs($announceManager, ['1200:0000:AB00:1234:O000:2552:7777:1313']));
    }

    public function testErrorResponseWithStringParameter()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();
        $error = 'Error xyz.';
        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $error]))
            ->willReturn($returnValue);

        $announceManager = new AnnounceManager(
            $encoder,
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $this->assertSame($returnValue, $method->invokeArgs($announceManager, [$error]));
    }

    public function testErrorResponseWithArrayParameter()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();
        $error = ['Error X.', 'Error Y.'];
        $errorMessage = 'Error X. Error Y.';
        $returnValue = 'something';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(['failure reason' => $errorMessage]))
            ->willReturn($returnValue);

        $announceManager = new AnnounceManager(
            $encoder,
            $this->app->make(DatabaseManager::class),
            $this->app->make(CacheManager::class),
            $this->app->make(ValidationFactory::class),
            $this->app->make(Translator::class)
        );
        $reflectionClass = new ReflectionClass(AnnounceManager::class);
        $method = $reflectionClass->getMethod('announceErrorResponse');
        $method->setAccessible(true);

        $this->assertSame($returnValue, $method->invokeArgs($announceManager, [$error]));
    }
}
