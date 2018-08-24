<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Generator;
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
    /**
     * @dataProvider torrentSizeV1TorrentDataProvider
     * @dataProvider torrentSizeV2TorrentDataProvider
     *
     * @param array $testDict
     * @param int   $expectedSize
     */
    public function testGettingTorrentSize(array $testDict, int $expectedSize): void
    {
        /** @var TorrentInfoService $torrentInfoService */
        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        $this->assertSame($expectedSize, $torrentInfoService->getTorrentSize($testDict));
    }

    /**
     * @dataProvider torrentInfoDictV1TorrentDataProvider
     * @dataProvider torrentInfoDictV2TorrentDataProvider
     *
     * @param array $testDict
     * @param array $expected
     */
    public function testGettingTorrentFileNamesAndSizesFromTorrentInfoDict(array $testDict, array $expected): void
    {
        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        $testData = iterator_to_array($torrentInfoService->getTorrentFileNamesAndSizesFromTorrentInfoDict($testDict));

        $this->assertSame($expected, $testData);
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

    public function torrentInfoDictV1TorrentDataProvider(): Generator
    {
        // multiple file mode
        yield [
            [
                'files' => [
                    [
                        'length' => 10,
                        'path' => ['folder', 'filename.txt'],
                    ],
                    [
                        'length' => 25,
                        'path' => ['folder2', 'filename2.txt'],
                    ],
                ],
            ],
            [
                'folder/filename.txt' => 10,
                'folder2/filename2.txt' => 25,
            ],
        ];

        // single file mode
        yield [
            ['name' => 'filename.txt', 'length' => 320],
            ['filename.txt' => 320],
        ];
    }

    public function torrentInfoDictV2TorrentDataProvider(): Generator
    {
        // one file in root
        yield [
            [
                'meta version' => 2,
                'file tree' => [
                    'fileA.txt' => [
                        '' => [
                            'length' => 555,
                        ],
                    ],
                ],
            ],
            [
                'fileA.txt' => 555,
            ],
        ];

        // multiple files in a single directory
        yield [
            [
                'meta version' => 2,
                'file tree' => [
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
                ],
            ],
            [
                'dir1/fileA.txt' => 100,
                'dir1/fileB.txt' => 200,
            ],
        ];

        // complex directory/file structure
        yield [
            [
                'meta version' => 2,
                'file tree' => [
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
                ],
            ],
            [
                'dir1/dir2/fileA.txt' => 500,
                'dir1/dir2/fileX.txt' => 1000,
                'dir1/dir2/dir3/dir4/fileC.txt' => 100,
                'dir1/dir2/dir3/fileB.txt' => 750,
            ],
        ];
    }

    public function torrentSizeV1TorrentDataProvider(): Generator
    {
        // multiple file mode
        yield [
            [
                'files' => [
                    ['length' => 10, 'path' => ['testA.txt']],
                    ['length' => 25, 'path' => ['testB.txt']],
                ],
            ],
            35,
        ];

        // single file mode
        yield [
            [
                'name' => 'testC.txt',
                'length' => 500,
            ],
            500,
        ];
    }

    public function torrentSizeV2TorrentDataProvider(): Generator
    {
        // single file torrent
        yield [
            [
                'meta version' => 2,
                'file tree' => [
                    'fileA.txt' => [
                        '' => [
                            'length' => 500,
                        ],
                    ],
                ],
            ],
            500,
        ];

        // multiple files rooted in a single directory
        yield [
            [
                'meta version' => 2,
                'file tree' => [
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
                ],
            ],
            300,
        ];

        // multiple nesting
        yield [
            [
                'meta version' => 2,
                'file tree' => [
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
                ],
            ],
            2800,
        ];
    }
}
