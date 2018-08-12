<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Services\IMDBManager;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use App\Services\IMDBImagesManager;
use App\Services\TorrentInfoService;
use Illuminate\Filesystem\Filesystem;
use App\Services\TorrentUploadManager;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class TorrentUploadManagerTest extends TestCase
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
        $torrentUploadManager = new TorrentUploadManager(
            $encoder,
            $decoder,
            $torrentInfoService,
            $this->app->make(AuthManager::class),
            $this->app->make(Filesystem::class),
            $this->app->make(FilesystemManager::class),
            $this->app->make(UrlGenerator::class),
            $this->app->make(Translator::class),
            $this->app->make(CacheManager::class),
            $this->app->make(IMDBManager::class),
            $this->app->make(IMDBImagesManager::class)
        );
        $reflectionClass = new ReflectionClass(TorrentUploadManager::class);
        $method = $reflectionClass->getMethod('getTorrentInfoHash');
        $method->setAccessible(true);

        $this->assertSame(sha1($returnValue), $method->invokeArgs($torrentUploadManager, [$array]));
    }
}
