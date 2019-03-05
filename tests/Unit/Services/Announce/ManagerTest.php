<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Announce;

use Tests\TestCase;
use ReflectionClass;
use App\Services\Bencoder;
use App\Services\Announce\Manager;
use App\Services\Announce\DataFactory;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Cache\Repository as CacheRepository;

class ManagerTest extends TestCase
{
//    public function testErrorResponseWithStringParameter()
//    {
//        /** @var MockObject|Bencoder $encoder */
////        $encoder = $this->getMockBuilder(Bencoder::class)
////            ->setMethods(['encode'])
////            ->getMock();
////        $error = 'Error xyz.';
////        $returnValue = 'something';
////        $encoder->expects($this->once())
////            ->method('encode')
////            ->with($this->equalTo(['failure reason' => $error]))
////            ->willReturn($returnValue);
//
//        $announceManager = new Manager(
//            $this->app->make(ConnectionInterface::class),
//            $this->app->make(CacheRepository::class),
//            $this->app->make(Translator::class),
//            $this->app->make(DataFactory::class)
//        );
//        $reflectionClass = new ReflectionClass(Manager::class);
//        $method = $reflectionClass->getMethod('announceErrorResponse');
//        $method->setAccessible(true);
//
//        $this->assertSame($returnValue, $method->invokeArgs($announceManager, [$error]));
//    }
//
//    public function testErrorResponseWithArrayParameter()
//    {
//        /** @var MockObject|Bencoder $encoder */
//        $encoder = $this->getMockBuilder(Bencoder::class)
//            ->setMethods(['encode'])
//            ->getMock();
//        $error = ['Error X.', 'Error Y.'];
//        $errorMessage = 'Error X. Error Y.';
//        $returnValue = 'something';
//        $encoder->expects($this->once())
//            ->method('encode')
//            ->with($this->equalTo(['failure reason' => $errorMessage]))
//            ->willReturn($returnValue);
//
//        $announceManager = new Manager(
//            $encoder,
//            $this->app->make(ConnectionInterface::class),
//            $this->app->make(CacheRepository::class),
//            $this->app->make(Translator::class),
//            $this->app->make(DataFactory::class)
//        );
//        $reflectionClass = new ReflectionClass(Manager::class);
//        $method = $reflectionClass->getMethod('announceErrorResponse');
//        $method->setAccessible(true);
//
//        $this->assertSame($returnValue, $method->invokeArgs($announceManager, [$error]));
//    }
}
