<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Services\IMDb\IMDBImagesManager;
use App\Services\IMDb\IMDBManager;
use App\Services\TorrentInfoService;
use App\Services\TorrentUploadManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Tests\TestCase;

class TorrentUploadManagerTest extends TestCase
{
    public function testGetV1TorrentInfoHash()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->onlyMethods(['encode'])
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
        $torrentUploadManager = new TorrentUploadManager(
            $encoder,
            $decoder,
            $torrentInfoService,
            $this->app->make(Guard::class),
            $this->app->make(Filesystem::class),
            $this->app->make(FilesystemManager::class),
            $this->app->make(UrlGenerator::class),
            $this->app->make(Translator::class),
            $this->app->make(IMDBManager::class),
            $this->app->make(IMDBImagesManager::class)
        );
        $reflectionClass = new ReflectionClass(TorrentUploadManager::class);
        $method = $reflectionClass->getMethod('getV1TorrentInfoHash');
        $method->setAccessible(true);

        $this->assertSame(sha1($returnValue), $method->invokeArgs($torrentUploadManager, [$array]));
    }

    public function testGetV2TruncatedTorrentInfoHash()
    {
        /** @var MockObject|Bencoder $encoder */
        $encoder = $this->getMockBuilder(Bencoder::class)
            ->onlyMethods(['encode'])
            ->getMock();
        $array = ['x' => 'foobar'];
        $returnValue = 'xyz264';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo($array))
            ->willReturn($returnValue);

        /** @var MockObject|Bdecoder $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var MockObject|TorrentInfoService $torrentInfoService */
        $torrentInfoService = $this->createMock(TorrentInfoService::class);
        $torrentUploadManager = new TorrentUploadManager(
            $encoder,
            $decoder,
            $torrentInfoService,
            $this->app->make(Guard::class),
            $this->app->make(Filesystem::class),
            $this->app->make(FilesystemManager::class),
            $this->app->make(UrlGenerator::class),
            $this->app->make(Translator::class),
            $this->app->make(IMDBManager::class),
            $this->app->make(IMDBImagesManager::class)
        );
        $reflectionClass = new ReflectionClass(TorrentUploadManager::class);
        $method = $reflectionClass->getMethod('getV2TruncatedTorrentInfoHash');
        $method->setAccessible(true);

        $this->assertSame(
            substr(hash('sha256', $returnValue), 0, 40),
            $method->invokeArgs($torrentUploadManager, [$array])
        );
    }

    public function testAreHashesUnique(): void
    {
        $torrentUploadManager = $this->app->make(TorrentUploadManager::class);
        $reflectionClass = new ReflectionClass(TorrentUploadManager::class);
        $method = $reflectionClass->getMethod('areHashesUnique');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs($torrentUploadManager, [['test123', 'test123']]));
    }
}
