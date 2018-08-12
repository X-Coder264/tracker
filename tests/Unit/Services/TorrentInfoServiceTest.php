<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\IMDBManager;
use App\Services\SizeFormatter;
use Illuminate\Cache\CacheManager;
use App\Services\TorrentInfoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class TorrentInfoServiceTest extends TestCase
{
    public function testGettingTorrentSize()
    {
        /** @var Bdecoder|MockObject $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var SizeFormatter|MockObject $formatter */
        $formatter = $this->createMock(SizeFormatter::class);

        $torrentInfoService = new TorrentInfoService(
            $formatter,
            $decoder,
            $this->app->make(CacheManager::class),
            $this->app->make(FilesystemManager::class),
            $this->app->make(IMDBManager::class)
        );
        // multiple file mode
        $torrentInfoDict['files'] = [
            ['length' => 10],
            ['length' => 25],
        ];
        $this->assertSame(35, $torrentInfoService->getTorrentSize($torrentInfoDict));

        // single file mode
        $torrentInfoDict2['length'] = 500;
        $this->assertSame($torrentInfoDict2['length'], $torrentInfoService->getTorrentSize($torrentInfoDict2));
    }

    public function testGettingTorrentFileNamesAndSizesFromTorrentInfoDict()
    {
        /** @var Bdecoder|MockObject $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var SizeFormatter|MockObject $formatter */
        $formatter = $this->createMock(SizeFormatter::class);
        $map = [
            [10, '10 B'],
            [25, '25 B'],
            [500, '500 B'],
        ];
        $formatter->expects($this->exactly(3))
            ->method('getFormattedSize')
            ->will($this->returnValueMap($map));

        $torrentInfoService = new TorrentInfoService(
            $formatter,
            $decoder,
            $this->app->make(CacheManager::class),
            $this->app->make(FilesystemManager::class),
            $this->app->make(IMDBManager::class)
        );
        // multiple file mode
        $torrentInfoDict['files'] = [
            [
                'length' => 10,
                'path' => ['folder', 'filename.txt'],
            ],
            [
                'length' => 25,
                'path' => ['folder2', 'filename2.txt'],
            ],
        ];
        $expectedResult = [
            ['filename.txt', '10 B'],
            ['filename2.txt', '25 B'],
        ];

        $this->assertSame(
            $expectedResult,
            $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict)
        );

        // single file mode
        $torrentInfoDict2 = ['name' => 'filename.txt', 'length' => 500];
        $expectedResult2 = [
            ['filename.txt', '500 B'],
        ];
        $this->assertSame(
            $expectedResult2,
            $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict2)
        );
    }

    public function testGetTorrentFileNamesAndSizes()
    {
        /** @var Bdecoder|MockObject $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var SizeFormatter|MockObject $formatter */
        $formatter = $this->createMock(SizeFormatter::class);

        $torrent = factory(Torrent::class)->make(['uploader_id' => 1, 'category_id' => 1]);

        $storageReturnValue = 'xyz';
        Storage::shouldReceive('disk->get')->once()->with("{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $returnValue = ['name' => 'test', 'length' => 500];
        /** @var TorrentInfoService|MockObject $torrentInfoService */
        $torrentInfoService = $this->getMockBuilder(TorrentInfoService::class)
            ->setConstructorArgs(
                [
                    $formatter,
                    $decoder,
                    $this->app->make(CacheManager::class),
                    $this->app->make(FilesystemManager::class),
                    $this->app->make(IMDBManager::class),
                ]
            )
            ->setMethods(['getTorrentFileNamesAndSizesFromTorrentInfoDict'])
            ->getMock();
        $torrentInfoService->expects($this->once())
            ->method('getTorrentFileNamesAndSizesFromTorrentInfoDict')
            ->with($this->equalTo($decoderReturnValue['info']))
            ->willReturn($returnValue);

        $this->assertSame($returnValue, $torrentInfoService->getTorrentFileNamesAndSizes($torrent));
        $this->assertSame($returnValue, Cache::get('torrent.' . $torrent->id . '.files'));
    }
}
