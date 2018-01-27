<?php

namespace Tests\Unit\Http\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use App\Http\Services\TorrentInfoService;
use App\Http\Services\TorrentUploadService;
use PHPUnit\Framework\MockObject\MockObject;

class TorrentUploadServiceTest extends TestCase
{
    public function testGetTorrentInfoHash()
    {
        /* @var MockObject|BencodingService $encoder */
        $encoder = $this->getMockBuilder(BencodingService::class)
            ->setMethods(['encode'])
            ->getMock();
        $array = ['x' => 'y'];
        $returnValue = 'xyz264';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo($array))
            ->willReturn($returnValue);

        /* @var MockObject|BdecodingService $decoder */
        $decoder = $this->createMock(BdecodingService::class);
        /* @var MockObject|TorrentInfoService $torrentInfoService */
        $torrentInfoService = $this->createMock(TorrentInfoService::class);
        $torrentUploadService = new TorrentUploadService($encoder, $decoder, $torrentInfoService);
        $reflectionClass = new ReflectionClass(TorrentUploadService::class);
        $method = $reflectionClass->getMethod('getTorrentInfoHash');
        $method->setAccessible(true);

        $this->assertSame(sha1($returnValue), $method->invokeArgs($torrentUploadService, [$array]));
    }
}
