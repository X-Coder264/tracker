<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\TorrentInfoService;
use App\Http\Services\TorrentUploadService;
use App\Http\Services\SizeFormattingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testTorrentUpload()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $decoder = $this->createMock(BdecodingService::class);
        $this->app->instance(BdecodingService::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $encoder = $this->createMock(BencodingService::class);
        $this->app->instance(BencodingService::class, $encoder);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->method('getTorrentSize')->willReturn($torrentSize);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::findOrFail(1);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        Storage::disk('public')->assertExists('torrents/1.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/1.torrent'));

        $formatter = new SizeFormattingService();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
    }

    public function testAllTorrentsGetThePrivateFlagSet()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR),
            'non private torrent',
            'application/x-bittorrent',
            null,
            null,
            true
        );

        $decoder = new BdecodingService();
        $decodedTorrent = $decoder->decode(file_get_contents($torrentFile->getRealPath()));
        $this->assertArrayNotHasKey('private', $decodedTorrent['info']);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::findOrFail(1);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        Storage::disk('public')->assertExists('torrents/1.torrent');
        $decodedTorrent = $decoder->decode(Storage::disk('public')->get('torrents/1.torrent'));
        $this->assertSame(1, $decodedTorrent['info']['private']);
    }

    public function testTorrentsHaveEntropySet()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR),
            'test',
            'application/x-bittorrent',
            null,
            null,
            true
        );

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::findOrFail(1);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        $decoder = new BdecodingService();
        Storage::disk('public')->assertExists('torrents/1.torrent');
        $decodedTorrent = $decoder->decode(Storage::disk('public')->get('torrents/1.torrent'));
        $this->assertSame(128, strlen($decodedTorrent['info']['entropy']));
    }

    public function testTorrentMustHaveAnUniqueInfoHash()
    {
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 0, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $decoder = $this->createMock(BdecodingService::class);
        $this->app->instance(BdecodingService::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $encoder = $this->createMock(BencodingService::class);
        $this->app->instance(BencodingService::class, $encoder);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->method('getTorrentSize')->willReturn($torrentSize);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentUploadService = $this->getMockBuilder(TorrentUploadService::class)
            ->setConstructorArgs([$encoder, $decoder, $infoService])
            ->setMethods(['getTorrentInfoHash'])
            ->getMock();
        $expectedHash = 'test hash 264';
        $torrentUploadService->expects($this->exactly(2))
            ->method('getTorrentInfoHash')
            ->will($this->onConsecutiveCalls($infoHash, $expectedHash));

        $this->app->instance(TorrentUploadService::class, $torrentUploadService);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::findOrFail(2);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        Storage::disk('public')->assertExists('torrents/2.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/2.torrent'));

        $formatter = new SizeFormattingService();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($expectedHash, $torrent->infoHash);
    }

    public function testTorrentsHaveTheCorrectAnnounceUrl()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR),
            'test',
            'application/x-bittorrent',
            null,
            null,
            true
        );

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::findOrFail(1);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        $decoder = new BdecodingService();
        Storage::disk('public')->assertExists('torrents/1.torrent');
        $decodedTorrent = $decoder->decode(Storage::disk('public')->get('torrents/1.torrent'));
        $this->assertSame(route('announce'), $decodedTorrent['announce']);
    }
}
