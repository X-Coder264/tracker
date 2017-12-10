<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Services\BdecodingService;
use App\Http\Services\TorrentInfoService;
use App\Http\Services\SizeFormattingService;
use PHPUnit\Framework\MockObject\MockObject;

class TorrentInfoServiceTest extends TestCase
{
    public function testGettingTorrentSize()
    {
        /** @var BdecodingService|MockObject $decoder */
        $decoder = $this->createMock(BdecodingService::class);
        /** @var SizeFormattingService|MockObject $formatter */
        $formatter = $this->createMock(SizeFormattingService::class);

        $torrentInfoService = new TorrentInfoService($formatter, $decoder);
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
        /** @var BdecodingService|MockObject $decoder */
        $decoder = $this->createMock(BdecodingService::class);
        $this->app->instance(BdecodingService::class, $decoder);
        /** @var SizeFormattingService|MockObject $formatter */
        $formatter = $this->createMock(SizeFormattingService::class);
        $map = [
            [10, '10 B'],
            [25, '25 B'],
            [500, '500 B'],
        ];
        $formatter->expects($this->exactly(3))
            ->method('getFormattedSize')
            ->will($this->returnValueMap($map));

        $torrentInfoService = new TorrentInfoService($formatter, $decoder);
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
}
