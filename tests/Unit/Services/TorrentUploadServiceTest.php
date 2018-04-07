<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Services\TorrentInfoService;
use App\Services\TorrentUploadService;
use PHPUnit\Framework\MockObject\MockObject;

class TorrentUploadServiceTest extends TestCase
{
    public function testGetTorrentInfoHash()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->setMethods(['encode'])
            ->getMock();
        $array = ['x' => 'y'];
        $returnValue = 'xyz264';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo($array))
            ->willReturn($returnValue);

        /** @var MockObject|Bdecoder $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var MockObject|TorrentInfoService $torrentInfoService */
        $torrentInfoService = $this->createMock(TorrentInfoService::class);
        $torrentUploadService = new TorrentUploadService($encoder, $decoder, $torrentInfoService);
        $reflectionClass = new ReflectionClass(TorrentUploadService::class);
        $method = $reflectionClass->getMethod('getTorrentInfoHash');
        $method->setAccessible(true);

        $this->assertSame(sha1($returnValue), $method->invokeArgs($torrentUploadService, [$array]));
    }
}
