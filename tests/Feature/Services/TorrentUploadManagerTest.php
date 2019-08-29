<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Exception;
use Tests\TestCase;
use App\Models\User;
use ReflectionClass;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use App\Models\TorrentCategory;
use App\Models\TorrentInfoHash;
use App\Services\SizeFormatter;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use App\Services\IMDb\IMDBManager;
use Illuminate\Cache\CacheManager;
use App\Services\TorrentInfoService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Filesystem\Filesystem;
use App\Services\TorrentUploadManager;
use Illuminate\Support\Facades\Storage;
use App\Services\IMDb\IMDBImagesManager;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use PHPUnit\Framework\ExpectationFailedException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class TorrentUploadManagerTest extends TestCase
{
    use DatabaseTransactions;

    public function testTorrentUploadWithAV1OnlyTorrent(): void
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

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'),
            'test',
            'application/x-bittorrent',
            null,
            true
        );

        Storage::fake('torrents');
        Storage::fake('imdb-images');

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => $torrentCategory->id,
            'imdb_url'    => 'https://www.imdb.com/title/tt0468569/',
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        $decodedUploadedTorrent = $decoder->decode($torrentFile->get());

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");

        $decodedTorrentThatWasActuallyUploaded = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame($decodedUploadedTorrent['creation date'], $decodedTorrentThatWasActuallyUploaded['creation date']);
        $this->assertSame($decodedUploadedTorrent['created by'], $decodedTorrentThatWasActuallyUploaded['created by']);
        $this->assertSame($decodedUploadedTorrent['info']['length'], $decodedTorrentThatWasActuallyUploaded['info']['length']);
        $this->assertSame($decodedUploadedTorrent['info']['name'], $decodedTorrentThatWasActuallyUploaded['info']['name']);
        $this->assertSame($decodedUploadedTorrent['info']['piece length'], $decodedTorrentThatWasActuallyUploaded['info']['piece length']);
        $this->assertSame($decodedUploadedTorrent['info']['pieces'], $decodedTorrentThatWasActuallyUploaded['info']['pieces']);
        $this->assertSame(1, $decodedTorrentThatWasActuallyUploaded['info']['private']);
        $this->assertSame(route('announce'), $decodedTorrentThatWasActuallyUploaded['announce']);

        $this->assertSame(5, (int) $torrent->getOriginal('size'));
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertSame('0468569', $torrent->imdb_id);

        try {
            Storage::disk('imdb-images')->assertExists('0468569.jpg');
        } catch (ExpectationFailedException $exception) {
            fwrite(STDERR, 'Looks like the IMDB package does not work at the moment as expected.' . PHP_EOL);
        }

        $this->assertSame(1, TorrentInfoHash::count());
        $infoHash = TorrentInfoHash::first();
        $this->assertSame(1, $infoHash->version);
        $encoder = new Bencoder();
        $this->assertSame(sha1($encoder->encode($decodedTorrentThatWasActuallyUploaded['info'])), $infoHash->info_hash);

        // the value must be flushed at the end
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);
    }

    public function testTorrentUploadWithAV2OnlyTorrent(): void
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

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 only torrent.torrent'),
            'v2 only torrent',
            'application/x-bittorrent',
            null,
            true
        );

        Storage::fake('torrents');

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => $torrentCategory->id,
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        $decodedUploadedTorrent = $decoder->decode($torrentFile->get());

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");

        $decodedTorrentThatWasActuallyUploaded = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame($decodedUploadedTorrent['info']['piece length'], $decodedTorrentThatWasActuallyUploaded['info']['piece length']);
        $this->assertSame($decodedUploadedTorrent['info']['name'], $decodedTorrentThatWasActuallyUploaded['info']['name']);
        $this->assertSame($decodedUploadedTorrent['info']['file tree'], $decodedTorrentThatWasActuallyUploaded['info']['file tree']);
        $this->assertSame(1, $decodedTorrentThatWasActuallyUploaded['info']['private']);
        $this->assertSame(2, $decodedTorrentThatWasActuallyUploaded['info']['meta version']);
        $this->assertSame(route('announce'), $decodedTorrentThatWasActuallyUploaded['announce']);

        $this->assertSame(1048576, (int) $torrent->getOriginal('size'));
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertNull($torrent->imdb_id);

        $this->assertSame(1, TorrentInfoHash::count());
        $infoHash = TorrentInfoHash::first();
        $this->assertSame(2, $infoHash->version);
        $encoder = new Bencoder();
        $this->assertSame(
            substr(hash('sha256', $encoder->encode($decodedTorrentThatWasActuallyUploaded['info'])), 0, 40),
            $infoHash->info_hash
        );

        // the value must be flushed at the end
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);
    }

    public function testTorrentUploadWithAHybridTorrent(): void
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

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'v2 hybrid torrent.torrent'),
            'v2 hybrid torrent',
            'application/x-bittorrent',
            null,
            true
        );

        Storage::fake('torrents');

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => $torrentCategory->id,
        ]);

        $this->assertSame(1, Torrent::count());

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        $decodedUploadedTorrent = $decoder->decode($torrentFile->get());

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");

        $decodedTorrentThatWasActuallyUploaded = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame($decodedUploadedTorrent['info']['piece length'], $decodedTorrentThatWasActuallyUploaded['info']['piece length']);
        $this->assertSame($decodedUploadedTorrent['info']['name'], $decodedTorrentThatWasActuallyUploaded['info']['name']);
        $this->assertSame($decodedUploadedTorrent['info']['file tree'], $decodedTorrentThatWasActuallyUploaded['info']['file tree']);
        $this->assertSame(1, $decodedTorrentThatWasActuallyUploaded['info']['private']);
        $this->assertSame(2, $decodedTorrentThatWasActuallyUploaded['info']['meta version']);
        $this->assertSame(route('announce'), $decodedTorrentThatWasActuallyUploaded['announce']);

        $this->assertSame(65536, (int) $torrent->getOriginal('size'));
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertNull($torrent->imdb_id);

        $this->assertSame(2, TorrentInfoHash::count());
        $v1InfoHash = TorrentInfoHash::first();
        $v2InfoHash = TorrentInfoHash::latest('id')->first();
        $this->assertSame(1, $v1InfoHash->version);
        $this->assertSame($torrent->id, $v1InfoHash->torrent_id);
        $this->assertSame(2, $v2InfoHash->version);
        $this->assertSame($torrent->id, $v2InfoHash->torrent_id);
        $encoder = new Bencoder();
        $encodedTorrentDict = $encoder->encode($decodedTorrentThatWasActuallyUploaded['info']);
        $this->assertSame(
            sha1($encodedTorrentDict),
            $v1InfoHash->info_hash
        );
        $this->assertSame(
            substr(hash('sha256', $encodedTorrentDict), 0, 40),
            $v2InfoHash->info_hash
        );

        // the value must be flushed at the end
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);
    }

    public function testTorrentUploadIfTheTorrentFileIsNotAValidV1NorV2File(): void
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $decoder = $this->createMock(Bdecoder::class);
        $this->app->instance(Bdecoder::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->expects($this->once())->method('getTorrentSize')->willReturn($torrentSize);
        $infoService->expects($this->once())->method('isV1Torrent')->willReturn(false);
        $infoService->expects($this->once())->method('isV2Torrent')->willReturn(false);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $this->assertSame(0, Torrent::count());

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('torrent', trans('messages.validation.torrent-upload-invalid-torrent-file'));
        $response->assertSessionHas('_old_input');
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
        $infoService->method('isV1Torrent')->willReturn(true);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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

    public function testTorrentUploadWhenIMDBManagerThrowsAnException(): void
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

        $torrentFile = new UploadedFile(
            realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'),
            'test',
            'application/x-bittorrent',
            null,
            true
        );

        Storage::fake('torrents');
        Storage::fake('imdb-images');

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

        $imdbManager = $this->createMock(IMDBManager::class);
        $imdbManager->expects($this->once())
            ->method('getIMDBIdFromFullURL')
            ->with('https://www.imdb.com/title/tt0468569/')
            ->willThrowException(new Exception());
        $this->app->instance(IMDBManager::class, $imdbManager);

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => $torrentFile,
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => $torrentCategory->id,
            'imdb_url'    => 'https://www.imdb.com/title/tt0468569/',
        ]);

        $torrent = Torrent::firstOrFail();

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success', trans('messages.torrents.store-successfully-uploaded-torrent.message'));

        $decoder = new Bdecoder();
        $decodedUploadedTorrent = $decoder->decode($torrentFile->get());

        Storage::disk('torrents')->assertExists("{$torrent->id}.torrent");

        $decodedTorrentThatWasActuallyUploaded = $decoder->decode(Storage::disk('torrents')->get("{$torrent->id}.torrent"));
        $this->assertSame($decodedUploadedTorrent['creation date'], $decodedTorrentThatWasActuallyUploaded['creation date']);
        $this->assertSame($decodedUploadedTorrent['created by'], $decodedTorrentThatWasActuallyUploaded['created by']);
        $this->assertSame($decodedUploadedTorrent['info']['length'], $decodedTorrentThatWasActuallyUploaded['info']['length']);
        $this->assertSame($decodedUploadedTorrent['info']['name'], $decodedTorrentThatWasActuallyUploaded['info']['name']);
        $this->assertSame($decodedUploadedTorrent['info']['piece length'], $decodedTorrentThatWasActuallyUploaded['info']['piece length']);
        $this->assertSame($decodedUploadedTorrent['info']['pieces'], $decodedTorrentThatWasActuallyUploaded['info']['pieces']);
        $this->assertSame(1, $decodedTorrentThatWasActuallyUploaded['info']['private']);
        $this->assertSame(route('announce'), $decodedTorrentThatWasActuallyUploaded['announce']);

        Storage::disk('imdb-images')->assertMissing('0468569.jpg');

        $this->assertSame(5, (int) $torrent->getOriginal('size'));
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
        $this->assertSame($user->id, $torrent->uploader_id);
        $this->assertSame($torrentCategory->id, $torrent->category_id);
        $this->assertNull($torrent->imdb_id);

        $this->assertSame(1, TorrentInfoHash::count());
        $infoHash = TorrentInfoHash::first();
        $this->assertSame(1, $infoHash->version);
        $encoder = new Bencoder();
        $this->assertSame(sha1($encoder->encode($decodedTorrentThatWasActuallyUploaded['info'])), $infoHash->info_hash);

        // the value must be flushed at the end
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.5');
        $this->assertNull($cachedTorrents);
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
        $infoService->method('isV1Torrent')->willReturn(true);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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
        $infoService->method('isV1Torrent')->willReturn(true);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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
        $torrentDescription = str_repeat('Test foo', 20);

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
            true
        );

        $decoder = new Bdecoder();
        $decodedTorrent = $decoder->decode(file_get_contents($torrentFile->getRealPath()));
        $this->assertArrayNotHasKey('private', $decodedTorrent['info']);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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
            true
        );

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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
        factory(Torrent::class)->states('hybrid')->create();
        $torrent = Torrent::with('infoHashes')->firstOrFail();
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
        $infoService->method('isV1Torrent')->willReturn(true);
        $infoService->method('isV2Torrent')->willReturn(true);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentUploadManager = $this->getMockBuilder(TorrentUploadManager::class)
            ->setConstructorArgs(
                [
                    $encoder,
                    $decoder,
                    $infoService,
                    $this->app->make(Guard::class),
                    $this->app->make(Filesystem::class),
                    $this->app->make(FilesystemManager::class),
                    $this->app->make(UrlGenerator::class),
                    $this->app->make(Translator::class),
                    $this->app->make(IMDBManager::class),
                    $this->app->make(IMDBImagesManager::class),
                ]
            )
            ->onlyMethods(['areHashesUnique', 'getV1TorrentInfoHash', 'getV2TruncatedTorrentInfoHash'])
            ->getMock();
        $torrentUploadManager->expects($this->exactly(4))
            ->method('areHashesUnique')
            ->will($this->onConsecutiveCalls(false, false, false, true));

        $torrentUploadManager->expects($this->exactly(4))
            ->method('getV1TorrentInfoHash')
            ->will($this->onConsecutiveCalls($torrent->infoHashes[0]->info_hash, 'xyz', 'test', 'foo'));

        $torrentUploadManager->expects($this->exactly(4))
            ->method('getV2TruncatedTorrentInfoHash')
            ->will($this->onConsecutiveCalls('xyz', $torrent->infoHashes[1]->info_hash, 'test', 'bar'));

        $this->app->instance(TorrentUploadManager::class, $torrentUploadManager);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), [
            'torrent'     => File::create('file.torrent'),
            'name'        => $torrentName,
            'description' => $torrentDescription,
            'category'    => factory(TorrentCategory::class)->create()->id,
        ]);

        $torrent = Torrent::latest('id')->with('infoHashes')->firstOrFail();

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
        $this->assertSame('foo', $torrent->infoHashes[0]->info_hash);
        $this->assertSame(1, $torrent->infoHashes[0]->version);
        $this->assertSame('bar', $torrent->infoHashes[1]->info_hash);
        $this->assertSame(2, $torrent->infoHashes[1]->version);
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
            true
        );

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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
        $infoService->method('isV1Torrent')->willReturn(true);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = str_repeat('Test foo', 20);

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

    public function testAreHashesUnique(): void
    {
        $torrent = factory(Torrent::class)->create();
        $infoHash = sha1(Str::random(200));
        factory(TorrentInfoHash::class)->create(['torrent_id' => $torrent->id, 'info_hash' => $infoHash]);

        $torrentUploadManager = $this->app->make(TorrentUploadManager::class);
        $reflectionClass = new ReflectionClass(TorrentUploadManager::class);
        $method = $reflectionClass->getMethod('areHashesUnique');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs($torrentUploadManager, [[$infoHash]]));
    }
}
