<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Services\TorrentDownloadManipulator;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Routing\UrlGenerator;
use PHPUnit\Framework\TestCase;

final class TorrentDownloadManipulatorTest extends TestCase
{
    public function testGettingTorrentContent(): void
    {
        $urlGenerator = $this->createMock(UrlGenerator::class);
        $urlGenerator->expects($this->once())->method('route')->with('announce')->willReturn('foo-url');
        $filesystemFactory = $this->createMock(Factory::class);
        $disk = $this->createMock(Filesystem::class);
        $disk->expects($this->once())->method('get')->with('1.torrent')->willReturn('d3:cow3:moo4:spam4:eggse');

        $filesystemFactory->expects($this->once())->method('disk')->willReturn($disk);

        $bdecoder = new Bdecoder();
        $torrentDownloadManipulator = new TorrentDownloadManipulator(
            $urlGenerator,
            $filesystemFactory,
            new Bencoder(),
            $bdecoder
        );

        $result = $torrentDownloadManipulator->getTorrentContent(1, 'foobar');
        $decodedResult = $bdecoder->decode($result);

        $this->assertSame(['announce' => 'foo-url', 'cow' => 'moo', 'spam' => 'eggs'], $decodedResult);
    }

    public function testGettingTorrentContentThrowsExceptionIfThereIsNoFilePresent(): void
    {
        $urlGenerator = $this->createMock(UrlGenerator::class);
        $urlGenerator->expects($this->never())->method('route');
        $filesystemFactory = $this->createMock(Factory::class);
        $disk = $this->createMock(Filesystem::class);
        $disk->expects($this->once())->method('get')->with('55.torrent')->willThrowException(new FileNotFoundException());

        $filesystemFactory->expects($this->once())->method('disk')->willReturn($disk);

        $torrentDownloadManipulator = new TorrentDownloadManipulator(
            $urlGenerator,
            $filesystemFactory,
            new Bencoder(),
            new Bdecoder()
        );

        $this->expectException(FileNotFoundException::class);
        $torrentDownloadManipulator->getTorrentContent(55, 'foobar');
    }

    /**
     * @dataProvider nameProvider
     */
    public function testGettingTorrentName(string $originalName, string $expectedName): void
    {
        $torrentDownloadManipulator = new TorrentDownloadManipulator(
            $this->createMock(UrlGenerator::class),
            $this->createMock(Factory::class),
            new Bencoder(),
            new Bdecoder()
        );

        $this->assertSame($expectedName, $torrentDownloadManipulator->getTorrentName($originalName));
    }

    /**
     * @dataProvider fallbackNameProvider
     */
    public function testGettingFallbackTorrentName(string $originalName, string $expectedName): void
    {
        $torrentDownloadManipulator = new TorrentDownloadManipulator(
            $this->createMock(UrlGenerator::class),
            $this->createMock(Factory::class),
            new Bencoder(),
            new Bdecoder()
        );

        $this->assertSame($expectedName, $torrentDownloadManipulator->getFallBackTorrentName($originalName));
    }

    public function nameProvider(): iterable
    {
        return [
            'it adds .torrent suffix'  => ['test', 'test.torrent'],
            'it replaces / character' => ['test/X', 'testX.torrent'],
            'it replaces \\ character' => ['test\\Y', 'testY.torrent'],
            'it replaces all of the above characters' => ['test / foo \\ bar %% X', 'test  foo  bar %% X.torrent'],
            'it does not replace the % character' => ['test%Z', 'test%Z.torrent'],
            'it does not convert characters to ASCII' => ['čćšđž čč', 'čćšđž čč.torrent'],
        ];
    }

    public function fallbackNameProvider(): iterable
    {
        return [
            'it adds .torrent suffix'  => ['test', 'test.torrent'],
            'it replaces / character' => ['test/X', 'testX.torrent'],
            'it replaces \\ character' => ['test\\Y', 'testY.torrent'],
            'it replaces % character' => ['test%Z', 'testZ.torrent'],
            'it replaces all of the above characters' => ['test / foo \\ bar %% X', 'test  foo  bar  X.torrent'],
            'it converts characters to ASCII' => ['čćšđž čč', 'ccsdz cc.torrent'],
        ];
    }
}
