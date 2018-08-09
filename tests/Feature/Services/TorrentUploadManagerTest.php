<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Http\Models\User;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use App\Services\TorrentInfoService;
use Illuminate\Filesystem\Filesystem;
use App\Services\TorrentUploadManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentUploadManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testTorrentUpload()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $decoder = $this->createMock(Bdecoder::class);
        $this->app->instance(Bdecoder::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $encoder = $this->createMock(Bencoder::class);
        $this->app->instance(Bencoder::class, $encoder);

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
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('public')->assertExists('torrents/1.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/1.torrent'));

        $formatter = new SizeFormatter();

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
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'non private torrent.torrent'),
            'non private torrent',
            'application/x-bittorrent',
            null,
            null,
            true
        );

        $decoder = new Bdecoder();
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
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

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
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'),
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
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        Storage::disk('public')->assertExists('torrents/1.torrent');
        $decodedTorrent = $decoder->decode(Storage::disk('public')->get('torrents/1.torrent'));
        $this->assertSame(128, strlen($decodedTorrent['info']['entropy']));
    }

    public function testTorrentMustHaveAnUniqueInfoHash()
    {
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        factory(Torrent::class)->create(['info_hash' => $infoHash, 'seeders' => 0, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $decoder = $this->createMock(Bdecoder::class);
        $this->app->instance(Bdecoder::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $encoder = $this->createMock(Bencoder::class);
        $this->app->instance(Bencoder::class, $encoder);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->method('getTorrentSize')->willReturn($torrentSize);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentUploadService = $this->getMockBuilder(TorrentUploadManager::class)
            ->setConstructorArgs(
                [
                    $encoder,
                    $decoder,
                    $infoService,
                    $this->app->make(AuthManager::class),
                    $this->app->make(Filesystem::class),
                    $this->app->make(FilesystemManager::class),
                    $this->app->make(UrlGenerator::class),
                    $this->app->make(Translator::class),
                ]
            )
            ->setMethods(['getTorrentInfoHash'])
            ->getMock();
        $expectedHash = 'test hash 264';
        $torrentUploadService->expects($this->exactly(2))
            ->method('getTorrentInfoHash')
            ->will($this->onConsecutiveCalls($infoHash, $expectedHash));

        $this->app->instance(TorrentUploadManager::class, $torrentUploadService);

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
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('public')->assertExists('torrents/2.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/2.torrent'));

        $formatter = new SizeFormatter();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($expectedHash, $torrent->info_hash);
    }

    public function testTorrentsHaveTheCorrectAnnounceUrl()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'),
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
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        Storage::disk('public')->assertExists('torrents/1.torrent');
        $decodedTorrent = $decoder->decode(Storage::disk('public')->get('torrents/1.torrent'));
        $this->assertSame(route('announce'), $decodedTorrent['announce']);
    }

    public function testTorrentGetsDeletedIfTheFileWasNotSuccessfullyWrittenToTheDisk()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::shouldReceive('disk->put')->once()->andReturn(false);

        $decoder = $this->createMock(Bdecoder::class);
        $this->app->instance(Bdecoder::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $encoder = $this->createMock(Bencoder::class);
        $this->app->instance(Bencoder::class, $encoder);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->method('getTorrentSize')->willReturn($torrentSize);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
        ]);

        $this->assertSame(0, Torrent::count());

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHas('error', trans('messages.file-not-writable-exception.error-message'));
        $response->assertSessionHas('_old_input');
    }
}
