<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use App\Services\IMDBManager;
use Illuminate\Http\Response;
use App\Models\TorrentCategory;
use App\Services\SizeFormatter;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Cache\CacheManager;
use App\Services\IMDBImagesManager;
use App\Services\TorrentInfoService;
use Illuminate\Filesystem\Filesystem;
use App\Services\TorrentUploadManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class TorrentUploadManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testTorrentUpload(): void
    {
        $this->withoutExceptionHandling();

        $torrentCategory = factory(TorrentCategory::class)->states('canHaveIMDB')->create();
        $user = factory(User::class)->create(['torrents_per_page' => 5]);
        $this->actingAs($user);

        $cacheManager = $this->app->make(CacheManager::class);
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);

        // put some stupid value there just so that we can assert at the end that it was flushed
        $cacheManager->tags('torrents')->put('torrents.page.1.perPage.5', 'something', 10);
        $value = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertSame('something', $value);

        Storage::fake('torrents');
        Storage::fake('imdb-images');

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
            'category'    => $torrentCategory->id,
            'imdb_url'    => 'https://www.imdb.com/title/tt0468569/',
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $this->assertSame($torrentValue, Storage::disk('torrents')->get("{$torrent->id}.torrent"));

        Storage::disk('imdb-images')->assertExists('0468569.jpg');

        $formatter = new SizeFormatter();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertSame('0468569', $torrent->imdb_id);

        // the value must be flushed at the end
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);
    }

    public function testTorrentUploadWithEmptyIMDBURL(): void
    {
        $this->withoutExceptionHandling();

        $torrentCategory = factory(TorrentCategory::class)->states('canHaveIMDB')->create();
        $user = factory(User::class)->create(['torrents_per_page' => 10]);
        $this->actingAs($user);

        $cacheManager = $this->app->make(CacheManager::class);
        $cachedTorrentsFirstPage = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.10');
        $cachedTorrentsSecondPage = $cacheManager->tags('torrents')->get('torrents.page.2.perPage.10');
        $this->assertNull($cachedTorrentsFirstPage);
        $this->assertNull($cachedTorrentsSecondPage);

        // put some stupid value there just so that we can assert at the end that it was flushed
        $cacheManager->tags('torrents')->put('torrents.page.1.perPage.10', 'something', 15);
        $cacheManager->tags('torrents')->put('torrents.page.2.perPage.10', 'something2', 15);
        $this->assertSame('something', $cacheManager->tags('torrents')->get('torrents.page.1.perPage.10'));
        $this->assertSame('something2', $cacheManager->tags('torrents')->get('torrents.page.2.perPage.10'));

        Storage::fake('torrents');
        Storage::fake('imdb-images');

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
            'category'    => $torrentCategory->id,
            'imdb_url'    => '',
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $this->assertSame($torrentValue, Storage::disk('torrents')->get("{$torrent->id}.torrent"));

        $formatter = new SizeFormatter();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertNull($torrent->imdb_id);

        // the values must be flushed at the end
        $cachedTorrentsFirstPage = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.10');
        $cachedTorrentsSecondPage = $cacheManager->tags('torrents')->get('torrents.page.2.perPage.10');
        $this->assertNull($cachedTorrentsFirstPage);
        $this->assertNull($cachedTorrentsSecondPage);
    }

    public function testTorrentDoesNotHaveIMDBDataEvenIfURLIsProvidedWhenTheCategoryCannotHaveIMDB(): void
    {
        $this->withoutExceptionHandling();

        $torrentCategory = factory(TorrentCategory::class)->states('cannotHaveIMDB')->create();
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('torrents');
        Storage::fake('imdb-images');

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
            'category'    => $torrentCategory->id,
            'imdb_url'    => 'https://www.imdb.com/title/tt0468569/',
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $this->assertSame($torrentValue, Storage::disk('torrents')->get("{$torrent->id}.torrent"));

        Storage::disk('imdb-images')->assertMissing('0468569.jpg');

        $formatter = new SizeFormatter();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertNull($torrent->imdb_id);
    }

    public function testTorrentDoesNotHaveIMDBIdIfInvalidIMDBURLIsSupplied(): void
    {
        $this->withoutExceptionHandling();

        $torrentCategory = factory(TorrentCategory::class)->states('canHaveIMDB')->create();
        $user = factory(User::class)->create(['torrents_per_page' => 5]);
        $this->actingAs($user);

        $cacheManager = $this->app->make(CacheManager::class);
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);

        // put some stupid value there just so that we can assert at the end that it was flushed
        $cacheManager->tags('torrents')->put('torrents.page.1.perPage.5', 'something', 10);
        $value = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertSame('something', $value);

        Storage::fake('torrents');

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
            'category'    => $torrentCategory->id,
            'imdb_url'    => 'https://wtf.com/888/',
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $this->assertSame($torrentValue, Storage::disk('torrents')->get("{$torrent->id}.torrent"));

        $formatter = new SizeFormatter();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertNull($torrent->imdb_id);

        // the value must be flushed at the end
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);
    }

    public function testTorrentUploadWhenTheBdecoderThrowsAnException(): void
    {
        $torrentCategory = factory(TorrentCategory::class)->states('canHaveIMDB')->create();
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('torrents');
        Storage::fake('imdb-images');

        $decoder = $this->createMock(Bdecoder::class);
        $decoder->method('decode')->willThrowException(new Exception());
        $this->app->instance(Bdecoder::class, $decoder);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => $torrentCategory->id,
            'imdb_url'    => 'https://www.imdb.com/title/tt0468569/',
        ]);

        $this->assertSame(0, Torrent::count());

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('torrent', trans('messages.validation.torrent-upload-invalid-torrent-file'));

        Storage::disk('imdb-images')->assertMissing('0468569.jpg');
    }

    public function testAllTorrentsGetThePrivateFlagSet(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('torrents');

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

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $decodedTorrent = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame(1, $decodedTorrent['info']['private']);
    }

    public function testTorrentsHaveEntropySet(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('torrents');

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

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $decodedTorrent = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame(128, strlen($decodedTorrent['info']['entropy']));
    }

    public function testTorrentMustHaveAnUniqueInfoHash(): void
    {
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        factory(Torrent::class)->create(['info_hash' => $infoHash, 'seeders' => 0, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('torrents');

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

        $torrentUploadManager = $this->getMockBuilder(TorrentUploadManager::class)
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
                    $this->app->make(CacheManager::class),
                    $this->app->make(IMDBManager::class),
                    $this->app->make(IMDBImagesManager::class),
                ]
            )
            ->setMethods(['getTorrentInfoHash'])
            ->getMock();
        $expectedHash = 'test hash 264';
        $torrentUploadManager->expects($this->exactly(2))
            ->method('getTorrentInfoHash')
            ->will($this->onConsecutiveCalls($infoHash, $expectedHash));

        $this->app->instance(TorrentUploadManager::class, $torrentUploadManager);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $torrent = Torrent::latest('id')->firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $this->assertSame($torrentValue, Storage::disk('torrents')->get("{$torrent->id}.torrent"));

        $formatter = new SizeFormatter();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($expectedHash, $torrent->info_hash);
    }

    public function testTorrentsHaveTheCorrectAnnounceUrl(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('torrents');

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

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");
        $decodedTorrent = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame(route('announce'), $decodedTorrent['announce']);
    }

    public function testTorrentGetsDeletedIfTheFileWasNotSuccessfullyWrittenToTheDisk(): void
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
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $this->assertSame(0, Torrent::count());

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHas('error', trans('messages.file-not-writable-exception.error-message'));
        $response->assertSessionHas('_old_input');
    }
}
