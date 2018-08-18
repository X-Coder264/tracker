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
    public function testGettingTorrentSizeForV1Torrents(): void
    {
        $torrentInfoService = $this->app->make(TorrentInfoService::class);
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

    public function testGettingTorrentSizeForV2Torrents(): void
    {
        $torrentInfoService = $this->app->make(TorrentInfoService::class);
        // single file torrent
        $torrentInfoDict['meta version'] = 2;
        $torrentInfoDict['file tree'] = [
            'fileA.txt' => [
                '' => [
                    'length' => 500,
                ],
            ],
        ];

        $this->assertSame(500, $torrentInfoService->getTorrentSize($torrentInfoDict));

        // multiple files rooted in a single directory
        $torrentInfoDict['meta version'] = 2;
        $torrentInfoDict['file tree'] = [
            'dir1' => [
                'fileA.txt' => [
                    '' => [
                        'length' => 100,
                    ],
                ],
                'fileB.txt' => [
                    '' => [
                        'length' => 200,
                    ],
                ],
            ],
        ];

        $this->assertSame(300, $torrentInfoService->getTorrentSize($torrentInfoDict));

        // multiple nesting
        $torrentInfoDict['meta version'] = 2;
        $torrentInfoDict['file tree'] = [
            'dir1' => [
                'dir2' => [
                    'fileA.txt' => [
                        '' => [
                            'length' => 500,
                        ],
                    ],
                    'fileB.txt' => [
                        '' => [
                            'length' => 1000,
                        ],
                    ],
                    'dir3' => [
                        'dir4' => [
                            'fileC.txt' => [
                                '' => [
                                    'length' => 100,
                                ],
                            ],
                        ],
                        'fileX.txt' => [
                            '' => [
                                'length' => 1200,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame(2800, $torrentInfoService->getTorrentSize($torrentInfoDict));
    }

    public function testGettingTorrentFileNamesAndSizesFromTorrentInfoDictForV1Torrents(): void
    {
        $torrentInfoService = $this->app->make(TorrentInfoService::class);

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
            ['filename.txt', '10.00 B'],
            ['filename2.txt', '25.00 B'],
        ];

        $this->assertSame(
            $expectedResult,
            $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict)
        );

        // single file mode
        $torrentInfoDict2 = ['name' => 'filename.txt', 'length' => 500];
        $expectedResult2 = [
            ['filename.txt', '500.00 B'],
        ];
        $this->assertSame(
            $expectedResult2,
            $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict2)
        );
    }

    public function testGettingTorrentFileNamesAndSizesFromTorrentInfoDictForV2Torrents(): void
    {
        $this->markTestSkipped('This feature is not implemented yet');

        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        // single file torrent
        $torrentInfoDict['meta version'] = 2;
        $torrentInfoDict['file tree'] = [
            'fileA.txt' => [
                '' => [
                    'length' => 500,
                ],
            ],
        ];

        $this->assertSame([['fileA.txt', '500.00 B']], $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict));

        // multiple files rooted in a single directory
        $torrentInfoDict['meta version'] = 2;
        $torrentInfoDict['file tree'] = [
            'dir1' => [
                'fileA.txt' => [
                    '' => [
                        'length' => 100,
                    ],
                ],
                'fileB.txt' => [
                    '' => [
                        'length' => 200,
                    ],
                ],
            ],
        ];

        $this->assertSame([['fileA.txt', '100.00 B'], ['fileB.txt', '200.00 B']], $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict));

        // multiple nesting
        $torrentInfoDict['meta version'] = 2;
        $torrentInfoDict['file tree'] = [
            'dir1' => [
                'dir2' => [
                    'fileA.txt' => [
                        '' => [
                            'length' => 500,
                        ],
                    ],
                    'fileX.txt' => [
                        '' => [
                            'length' => 1000,
                        ],
                    ],
                    'dir3' => [
                        'dir4' => [
                            'fileC.txt' => [
                                '' => [
                                    'length' => 100,
                                ],
                            ],
                        ],
                        'fileB.txt' => [
                            '' => [
                                'length' => 750,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame(
            [['fileA.txt', '500.00 B'], ['fileX.txt', '1000.00 B'], ['fileC.txt', '100.00 B'], ['fileB.txt', '750.00 B']],
            $torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($torrentInfoDict)
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

    public function testIsV1Torrent(): void
    {
        $decoder = new Bdecoder();
        $decodedV1OnlyTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'));
        $decodedV2OnlyTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 only torrent.torrent'));
        $decodedHybridTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 hybrid torrent.torrent'));
        $torrentInfoService = $this->app->make(TorrentInfoService::class);
        $this->assertTrue($torrentInfoService->isV1Torrent($decodedV1OnlyTorrent['info']));
        $this->assertFalse($torrentInfoService->isV1Torrent($decodedV2OnlyTorrent['info']));
        $this->assertTrue($torrentInfoService->isV1Torrent($decodedHybridTorrent['info']));
    }

    public function testIsV2Torrent(): void
    {
        $decoder = new Bdecoder();
        $decodedV1OnlyTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'));
        $decodedV2OnlyTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 only torrent.torrent'));
        $decodedHybridTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 hybrid torrent.torrent'));
        $torrentInfoService = $this->app->make(TorrentInfoService::class);
        $this->assertFalse($torrentInfoService->isV2Torrent($decodedV1OnlyTorrent['info']));
        $this->assertTrue($torrentInfoService->isV2Torrent($decodedV2OnlyTorrent['info']));
        $this->assertTrue($torrentInfoService->isV2Torrent($decodedHybridTorrent['info']));
    }

    public function testIsHybridTorrent(): void
    {
        $decoder = new Bdecoder();
        $decodedV1OnlyTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'));
        $decodedV2OnlyTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 only torrent.torrent'));
        $decodedHybridTorrent = $decoder->decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 hybrid torrent.torrent'));
        $torrentInfoService = $this->app->make(TorrentInfoService::class);
        $this->assertFalse($torrentInfoService->isHybridTorrent($decodedV1OnlyTorrent['info']));
        $this->assertFalse($torrentInfoService->isHybridTorrent($decodedV2OnlyTorrent['info']));
        $this->assertTrue($torrentInfoService->isHybridTorrent($decodedHybridTorrent['info']));
    }
}
