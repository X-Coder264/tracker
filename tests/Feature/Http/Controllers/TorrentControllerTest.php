<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use Illuminate\Http\Response;
use InvalidArgumentException;
use App\Models\TorrentComment;
use App\Presenters\IMDb\Title;
use App\Models\TorrentCategory;
use App\Services\SizeFormatter;
use Illuminate\Http\Testing\File;
use App\Services\IMDb\IMDBManager;
use Illuminate\Cache\CacheManager;
use App\Services\IMDb\TitleFactory;
use App\Services\TorrentInfoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\FileSizeCollectionFormatter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class TorrentControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User
     */
    private $user;

    /**
     * @var int
     */
    private $torrentsPerPage = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create(['torrents_per_page' => $this->torrentsPerPage]);
        $this->actingAs($this->user);
    }

    public function testIndex(): void
    {
        $this->withoutExceptionHandling();

        $visibleTorrent = factory(Torrent::class)->states('alive')->create(['uploader_id' => $this->user->id, 'name' => 'test']);
        $deadTorrent = factory(Torrent::class)->states('dead')->create(['uploader_id' => $this->user->id]);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.index'));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.index');
        $response->assertViewHas(['torrents', 'timezone']);
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('torrents'));
        $this->assertSame(1, $response->viewData('torrents')->count());
        $this->assertSame($this->user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($response->viewData('torrents')[0]->is($visibleTorrent));
        $response->assertSee($visibleTorrent->name);
        $response->assertSee($visibleTorrent->uploader->name);
        $response->assertSee($visibleTorrent->category->name);
        $response->assertDontSee($deadTorrent->name);

        $cacheManager = $this->app->make(CacheManager::class);
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.' . $this->torrentsPerPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $cachedTorrents);
        $this->assertSame(1, $cachedTorrents->count());
        $this->assertSame($this->user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($cachedTorrents[0]->is($visibleTorrent));

        $cacheManager->tags('torrents')->flush();
    }

    public function testIndexWithNonNumericPage(): void
    {
        $this->withoutExceptionHandling();

        $visibleTorrent = factory(Torrent::class)->states('alive')->create(['uploader_id' => $this->user->id, 'name' => 'test']);
        $deadTorrent = factory(Torrent::class)->states('dead')->create(['uploader_id' => $this->user->id]);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.index', ['page' => 'invalid-string']));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.index');
        $response->assertViewHas(['torrents', 'timezone']);
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->original->torrents);
        $this->assertSame(1, $response->viewData('torrents')->count());
        $this->assertSame($this->user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($response->original->torrents[0]->is($visibleTorrent));
        $response->assertSee($visibleTorrent->name);
        $response->assertSee($visibleTorrent->uploader->name);
        $response->assertDontSee($deadTorrent->name);

        $cacheManager = $this->app->make(CacheManager::class);
        $cachedTorrents = $cacheManager->tags('torrents')->get('torrents.page.1.perPage.' . $this->torrentsPerPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $cachedTorrents);
        $this->assertSame(1, $cachedTorrents->count());
        $this->assertSame($this->user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($cachedTorrents[0]->is($visibleTorrent));

        $cacheManager->tags('torrents')->flush();
    }

    public function testCreate(): void
    {
        $this->withoutExceptionHandling();

        $urlGenerator = $this->app->make(UrlGenerator::class);

        /** @var TorrentCategory[] $categories */
        $categories = factory(TorrentCategory::class, 2)->create();

        $response = $this->get($urlGenerator->route('torrents.create'));

        $response->assertOk();
        $response->assertViewIs('torrents.create');

        $response->assertViewHas('torrent');
        $this->assertInstanceOf(Torrent::class, $response->original->gatherData()['torrent']);
        $response->assertViewHas('categories', function (Collection $collection) use ($categories) {
            return 2 === $collection->count() && $collection->contains($categories[0]) && $collection->contains($categories[1]);
        });
        $response->assertViewHas('formActionUrl', $urlGenerator->route('torrents.store'));
    }

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->states('hybrid')
            ->create(
                [
                    'uploader_id' => $this->user->id,
                    'seeders' => 501,
                    'leechers' => 333,
                    'imdb_id' => '0468569',
                    'views_count' => 0,
                ]
            );

        $torrentComment = factory(TorrentComment::class)->create(
            ['torrent_id' => $torrent->id, 'user_id' => $torrent->uploader_id]
        );
        $peer = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $this->user->id,
                'uploaded'   => 6144,
                'downloaded' => 2048,
            ]
        );

        $torrentInfo = $this->getMockBuilder(TorrentInfoService::class)
            ->setConstructorArgs(
                [
                    $this->app->make(SizeFormatter::class),
                    $this->app->make(Bdecoder::class),
                    $this->app->make(Repository::class),
                    $this->app->make(FilesystemManager::class),
                    $this->app->make(IMDBManager::class),
                    $this->app->make(TitleFactory::class),
                ]
            )
            ->onlyMethods(['getTorrentFileNamesAndSizes'])
            ->getMock();

        $returnValue = [
            'Test.txt' => 999999999,
        ];

        /** @var FileSizeCollectionFormatter $formatter */
        $formatter = $this->app->make(FileSizeCollectionFormatter::class);

        $torrentInfo->method('getTorrentFileNamesAndSizes')->willReturn($returnValue);
        $this->app->instance(TorrentInfoService::class, $torrentInfo);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.show', $torrent));
        $response->assertOk();
        $response->assertViewIs('torrents.show');
        $response->assertViewHas('torrent', $torrent);
        $response->assertViewHas('cachedTorrent', $torrent);
        $response->assertViewHas('numberOfPeers', 1);
        $response->assertViewHas('torrentFileNamesAndSizes', $formatter->format($returnValue));
        $response->assertViewHas('filesCount', 1);
        $response->assertViewHas('torrentComments');
        $response->assertViewHas('imdbData');
        $response->assertViewHas('posterExists', false);
        $response->assertViewHas('user', $this->user);
        $response->assertViewHas('timezone', $this->user->timezone);

        $cache = $this->app->make(Repository::class);

        $cachedTorrent = $cache->get('torrent.' . $torrent->id);
        $this->assertInstanceOf(Torrent::class, $torrent);
        $this->assertTrue($torrent->is($cachedTorrent));

        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('torrentComments'));
        $this->assertSame(10, $response->viewData('torrentComments')->perPage());
        $this->assertSame(1, $response->viewData('torrentComments')->total());
        $this->assertSame(1, $response->viewData('torrentComments')->currentPage());
        $this->assertTrue($torrentComment->is($response->original->torrentComments[0]));

        /** @var LengthAwarePaginator $cachedTorrentComments */
        $cachedTorrentComments = $cache->get(sprintf('torrent.%d.comments.page.%d', $torrent->id, 1));
        $this->assertInstanceOf(LengthAwarePaginator::class, $cachedTorrentComments);
        $this->assertSame(10, $cachedTorrentComments->perPage());
        $this->assertSame(1, $cachedTorrentComments->total());
        $this->assertSame(1, $cachedTorrentComments->currentPage());
        $this->assertTrue($torrentComment->is($cachedTorrentComments[0]));

        $this->assertInstanceOf(Title::class, $response->viewData('imdbData'));
        $this->assertSame('0468569', $response->viewData('imdbData')->getId());
        $response->assertSee($torrent->name);
        $response->assertSee($torrent->description);
        $response->assertSee($torrent->size);
        $response->assertSee($torrent->seeders);
        $response->assertSee($torrent->leechers);
        $response->assertSee($torrent->infoHashes[0]->info_hash);
        $response->assertSee($torrent->infoHashes[1]->info_hash);
        $response->assertSee($torrent->uploader->name);
        $response->assertSee($torrent->category->name);
        $response->assertSee($torrentComment->comment);
        $response->assertSee($peer->user->name);
        $response->assertSee($response->viewData('imdbData')->getName());
        $response->assertSee($response->viewData('imdbData')->getRating());
        $response->assertSee($response->viewData('imdbData')->getPlotOutline());
        $response->assertSee(implode(', ', $response->viewData('imdbData')->getGenres()));
        // peer downloaded stats
        $response->assertSee('2.00 KiB');
        // peer uploaded stats
        $response->assertSee('6.00 KiB');
        // peer ratio
        $response->assertSee('3.00');
        $response->assertSee($peer->userAgent);
        $response->assertSee('953.67 MiB');
        $response->assertSee('Test.txt');
        $torrent->refresh();
        $this->assertSame(1, $torrent->views_count);
    }

    public function testShowWhenTorrentInfoServiceThrowsAnException(): void
    {
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $torrentInfo = $this->createMock(TorrentInfoService::class);
        $this->app->instance(TorrentInfoService::class, $torrentInfo);

        $torrentInfo->method('getTorrentFileNamesAndSizes')->will($this->throwException(new FileNotFoundException()));

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertSee(trans('messages.torrent-file-missing.error-message'));
        $response->assertDontSee($torrent->name);
    }

    public function testEdit(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('torrents.edit', $torrent));

        $response->assertOk();
        $response->assertViewIs('torrents.edit');
        $response->assertViewHas('torrent', $torrent);
        $response->assertViewHas('categories', function (Collection $collection) use ($torrent) {
            return 1 === $collection->count() && $collection->contains($torrent->category);
        });
        $response->assertViewHas('formActionUrl', $urlGenerator->route('torrents.update', $torrent));
    }

    public function testEditWhenTheUserIsNotTheUploaderOfTheTorrent(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create();

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('torrents.edit', $torrent));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.index'));
        $response->assertSessionHas(
            'error',
            $this->app->make(Translator::class)->trans('messages.torrent.not_allowed_to_edit')
        );
    }

    public function testUpdate(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $category = factory(TorrentCategory::class)->create(['imdb' => true]);

        $imdbManager = $this->createMock(IMDBManager::class);
        $imdbManager->expects($this->once())
            ->method('getIMDBIdFromFullURL')
            ->with('https://www.imdb.com/title/tt0468575/')
            ->willReturn('0468575');

        $this->app->instance(IMDBManager::class, $imdbManager);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $name = 'test foo 123';
        $description = str_repeat('Foo bar', 55);

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            [
                'name' => $name,
                'description' => $description,
                'category' => $category->id,
                'imdb_url' => 'https://www.imdb.com/title/tt0468575/',
            ]
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.edit', $torrent));
        $response->assertSessionHas(
            'success',
            $this->app->make(Translator::class)->trans('messages.torrent.successfully_updated')
        );

        $torrent->refresh();

        $this->assertSame($name, $torrent->name);
        $this->assertSame($description, $torrent->description);
        $this->assertSame('0468575', $torrent->imdb_id);
        $this->assertTrue($torrent->category->is($category));
    }

    public function testUpdateWithInvalidName(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        factory(TorrentCategory::class)->create(['imdb' => true]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $name = 'xy';

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            $this->validParams(['name' => $name])
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.edit', $torrent));
        $response->assertSessionHasErrors('name');

        $freshTorrent = $torrent->fresh();

        $this->assertSame($torrent->name, $freshTorrent->name);
        $this->assertSame($torrent->description, $freshTorrent->description);
        $this->assertSame($torrent->imdb_id, $freshTorrent->imdb_id);
        $this->assertTrue($freshTorrent->category->is($torrent->category));
    }

    public function testUpdateWithInvalidDescription(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $category = factory(TorrentCategory::class)->create(['imdb' => true]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $description = 'xy';

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            $this->validParams(['description' => $description])
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.edit', $torrent));
        $response->assertSessionHasErrors('description');

        $freshTorrent = $torrent->fresh();

        $this->assertSame($torrent->name, $freshTorrent->name);
        $this->assertSame($torrent->description, $freshTorrent->description);
        $this->assertSame($torrent->imdb_id, $freshTorrent->imdb_id);
        $this->assertTrue($freshTorrent->category->is($torrent->category));
    }

    public function testUpdateWithInvalidCategory(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        factory(TorrentCategory::class)->create(['imdb' => true]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            $this->validParams(['category' => 9999])
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.edit', $torrent));
        $response->assertSessionHasErrors('category');

        $freshTorrent = $torrent->fresh();

        $this->assertSame($torrent->name, $freshTorrent->name);
        $this->assertSame($torrent->description, $freshTorrent->description);
        $this->assertSame($torrent->imdb_id, $freshTorrent->imdb_id);
        $this->assertTrue($freshTorrent->category->is($torrent->category));
    }

    public function testUpdateWithInvalidImdbUrl(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        factory(TorrentCategory::class)->create(['imdb' => true]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            $this->validParams(['imdb_url' => 'foobar'])
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.edit', $torrent));
        $response->assertSessionHasErrors('imdb_url');

        $freshTorrent = $torrent->fresh();

        $this->assertSame($torrent->name, $freshTorrent->name);
        $this->assertSame($torrent->description, $freshTorrent->description);
        $this->assertSame($torrent->imdb_id, $freshTorrent->imdb_id);
        $this->assertTrue($freshTorrent->category->is($torrent->category));
    }

    public function testUpdateWhenTheUserIsNotTheUploaderOfTheTorrent(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create();

        $category = factory(TorrentCategory::class)->create(['imdb' => true]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $name = 'test foo 123';
        $description = str_repeat('Foo bar', 55);

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            [
                'name' => $name,
                'description' => $description,
                'category' => $category->id,
                'imdb_url' => 'https://www.imdb.com/title/tt0468575/',
            ]
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.index'));
        $response->assertSessionHas(
            'error',
            $this->app->make(Translator::class)->trans('messages.torrent.not_allowed_to_edit')
        );

        $freshTorrent = $torrent->fresh();

        $this->assertSame($torrent->name, $freshTorrent->name);
        $this->assertSame($torrent->description, $freshTorrent->description);
        $this->assertSame($torrent->imdb_id, $freshTorrent->imdb_id);
        $this->assertTrue($freshTorrent->category->is($torrent->category));
    }

    public function testUpdateWhenImdbLinkParsingThrowsAnException(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $category = factory(TorrentCategory::class)->create(['imdb' => true]);

        $imdbManager = $this->createMock(IMDBManager::class);
        $imdbManager->expects($this->once())
            ->method('getIMDBIdFromFullURL')
            ->willThrowException(new InvalidArgumentException());

        $this->app->instance(IMDBManager::class, $imdbManager);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $name = 'test foo 123';
        $description = str_repeat('Foo bar', 55);

        $response = $this->from($urlGenerator->route('torrents.edit', $torrent))->put(
            $urlGenerator->route('torrents.update', $torrent),
            [
                'name' => $name,
                'description' => $description,
                'category' => $category->id,
                'imdb_url' => 'https://www.imdb.com/title/tt0468575/',
            ]
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('torrents.edit', $torrent));
        $response->assertSessionHas(
            'success',
            $this->app->make(Translator::class)->trans('messages.torrent.successfully_updated')
        );

        $torrent->refresh();

        $this->assertSame($name, $torrent->name);
        $this->assertSame($description, $torrent->description);
        $this->assertNull($torrent->imdb_id);
        $this->assertTrue($torrent->category->is($category));
    }

    public function testDownloadWithASCIITorrentFileName(): void
    {
        $this->withoutExceptionHandling();

        /** @var Bdecoder|MockObject $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var Bencoder|MockObject $encoder */
        $encoder = $this->createMock(Bencoder::class);

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'name' => 'xyz']);

        $storageReturnValue = 'something x264';
        Storage::shouldReceive('disk->get')->once()->with("{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $this->app->instance(Bdecoder::class, $decoder);

        $encoderReturnValue = 'something xyz';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(array_merge($decoderReturnValue, ['announce' => $this->app->make(UrlGenerator::class)->route('announce', ['passkey' => $this->user->passkey])])))
            ->willReturn($encoderReturnValue);

        $this->app->instance(Bencoder::class, $encoder);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame($encoderReturnValue, $response->getContent());
        $response->assertHeader('Content-Type', 'application/x-bittorrent');
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $torrent->name . '.torrent');
    }

    public function testDownloadWithUTF8TorrentFileNameWhichIncludesSpecialCharacters(): void
    {
        $this->withoutExceptionHandling();

        /** @var Bdecoder|MockObject $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var Bencoder|MockObject $encoder */
        $encoder = $this->createMock(Bencoder::class);

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'name' => 'čćš/đž%']);

        $storageReturnValue = 'something x264';
        Storage::shouldReceive('disk->get')->once()->with("{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $this->app->instance(Bdecoder::class, $decoder);

        $encoderReturnValue = 'something xyz';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(array_merge($decoderReturnValue, ['announce' => $this->app->make(UrlGenerator::class)->route('announce', ['passkey' => $this->user->passkey])])))
            ->willReturn($encoderReturnValue);

        $this->app->instance(Bencoder::class, $encoder);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame($encoderReturnValue, $response->getContent());
        $response->assertHeader('Content-Type', 'application/x-bittorrent');
        $fileName = str_replace(['/', '\\'], '', $torrent->name . '.torrent');
        $dispositionHeader = sprintf(
            '%s; filename=%s' . "; filename*=utf-8''%s",
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'ccsdz.torrent',
            rawurlencode($fileName)
        );
        $response->assertHeader('Content-Disposition', $dispositionHeader);
    }

    public function testDownloadWhenStorageThrowsAnException(): void
    {
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        Storage::shouldReceive('disk->get')->once()->with("{$torrent->id}.torrent")->andThrow(new FileNotFoundException());
        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertSee(trans('messages.torrent-file-missing.error-message'));
        $response->assertDontSee($torrent->name);
    }

    public function testGuestDownloadWithProvidedPasskey(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make('auth')->guard()->logout();

        /** @var Bdecoder|MockObject $decoder */
        $decoder = $this->createMock(Bdecoder::class);
        /** @var Bencoder|MockObject $encoder */
        $encoder = $this->createMock(Bencoder::class);

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'name' => 'xyz']);

        $storageReturnValue = 'something x264';
        Storage::shouldReceive('disk->get')->once()->with("{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $this->app->instance(Bdecoder::class, $decoder);

        $encoderReturnValue = 'something xyz';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(array_merge($decoderReturnValue, ['announce' => $this->app->make(UrlGenerator::class)->route('announce', ['passkey' => $this->user->passkey])])))
            ->willReturn($encoderReturnValue);

        $this->app->instance(Bencoder::class, $encoder);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.download', ['torrent' => $torrent, 'passkey' => $this->user->passkey]));
        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame($encoderReturnValue, $response->getContent());
        $response->assertHeader('Content-Type', 'application/x-bittorrent');
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $torrent->name . '.torrent');
    }

    public function testGuestsCannotDownloadWithAnInvalidPasskey(): void
    {
        $this->app->make('auth')->guard()->logout();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'name' => 'xyz']);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.download', ['torrent' => $torrent, 'passkey' => 'does-not-exist']));
        $response->assertStatus(302);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('login'));
    }

    public function testGuestsCannotSeeTheTorrentsIndexPage(): void
    {
        $this->app->make('auth')->guard()->logout();
        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.index'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('login'));
    }

    public function testGuestsCannotSeeTheTorrentPage(): void
    {
        $this->app->make('auth')->guard()->logout();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('login'));
    }

    public function testGuestsCannotDownloadTorrentsIfPasskeyIsNotProvided(): void
    {
        $this->app->make('auth')->guard()->logout();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        $response = $this->get($this->app->make(UrlGenerator::class)->route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('login'));
    }

    public function testGuestsCannotUploadTorrents(): void
    {
        $this->app->make('auth')->guard()->logout();
        $response = $this->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams());
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('login'));
        $this->assertSame(0, Torrent::count());
    }

    public function testTorrentFileIsRequired(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'torrent' => null,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('torrent');
        $this->assertSame(0, Torrent::count());
    }

    public function testTorrentMustBeAFile(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'torrent' => 'test string',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('torrent');
        $this->assertSame(0, Torrent::count());
    }

    public function testFileMustBeATorrent(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'torrent' => File::create('file.png'),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('torrent');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameIsRequired(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'name' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustContainAtLeast5Chars(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'name' => str_repeat('X', 4),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustBeLessThan256CharsLong(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'name' => str_repeat('X', 256),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustBeUnique(): void
    {
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'name' => $torrent->name,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Torrent::count());
    }

    public function testDescriptionIsRequired(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'description' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('description');
        $this->assertSame(0, Torrent::count());
    }

    public function testCategoryIsRequired(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'category' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('category');
        $this->assertSame(0, Torrent::count());
    }

    public function testCategoryMustExistInDatabase(): void
    {
        $response = $this->from($this->app->make(UrlGenerator::class)->route('torrents.create'))->post($this->app->make(UrlGenerator::class)->route('torrents.store'), $this->validParams([
            'category' => '9999999',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('torrents.create'));
        $response->assertSessionHasErrors('category');
        $this->assertSame(0, Torrent::count());
    }

    private function validParams(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Test name',
            'description' => str_repeat('Test foobar', 5),
            'torrent'     => File::create('file.torrent'),
            'category'    => factory(TorrentCategory::class)->create()->id,
        ], $overrides);
    }
}
