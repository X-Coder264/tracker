<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use App\Http\Models\TorrentComment;
use App\Http\Services\PasskeyService;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\TorrentInfoService;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class TorrentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var User
     */
    private $user;

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->actingAs($this->user);
    }

    public function testIndex()
    {
        $this->withoutExceptionHandling();

        $visibleTorrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'seeders' => 1]);
        $deadTorrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'seeders' => 0]);

        $response = $this->get(route('torrents.index'));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.index');
        $response->assertViewHas(['torrents', 'timezone']);
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->original->torrents);
        $response->assertSee($visibleTorrent->name);
        $response->assertSee($visibleTorrent->uploader->name);
        $response->assertDontSee($deadTorrent->name);
    }

    public function testCreate()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('torrents.create'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.create');
    }

    public function testShow()
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
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

        $torrentInfo = $this->createMock(TorrentInfoService::class);
        $returnValue = [['Test.txt', '55.55 MiB']];
        $torrentInfo->method('getTorrentFileNamesAndSizes')->willReturn($returnValue);
        $this->app->instance(TorrentInfoService::class, $torrentInfo);

        $response = $this->get(route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.show');
        $response->assertViewHas('torrent');
        $response->assertViewHas('numberOfPeers', 1);
        $response->assertViewHas('torrentFileNamesAndSizes', $returnValue);
        $response->assertViewHas('torrentComments');
        $response->assertViewHas('timezone', $this->user->timezone);
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->original->torrentComments);
        $response->assertSee($torrent->name);
        $response->assertSee($torrent->description);
        $response->assertSee($torrentComment->comment);
        $response->assertSee($peer->user->name);
        $response->assertSee($peer->updated_at->diffForHumans());
        $response->assertSee('2.00 KiB');
        $response->assertSee('6.00 KiB');
        $response->assertSee('3.00');
        $response->assertSee($peer->userAgent);
        $response->assertSee('55.55 MiB');
        $response->assertSee('Test.txt');
    }

    public function testShowWhenTorrentInfoServiceThrowsAnException()
    {
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $torrentInfo = $this->createMock(TorrentInfoService::class);
        $this->app->instance(TorrentInfoService::class, $torrentInfo);

        $torrentInfo->method('getTorrentFileNamesAndSizes')->will($this->throwException(new FileNotFoundException()));

        $response = $this->get(route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertSee(__('messages.torrent-file-missing.error-message'));
        $response->assertDontSee($torrent->name);
    }

    public function testDownloadWithASCIITorrentFileName()
    {
        $this->withoutExceptionHandling();

        /** @var BdecodingService|MockObject $decoder */
        $decoder = $this->createMock(BdecodingService::class);
        /** @var BencodingService|MockObject $encoder */
        $encoder = $this->createMock(BencodingService::class);

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'name' => 'xyz']);

        $storageReturnValue = 'something x264';
        Storage::shouldReceive('disk->get')->once()->with("torrents/{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $this->app->instance(BdecodingService::class, $decoder);

        $encoderReturnValue = 'something xyz';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(array_merge($decoderReturnValue, ['announce' => route('announce', ['passkey' => $this->user->passkey])])))
            ->willReturn($encoderReturnValue);

        $this->app->instance(BencodingService::class, $encoder);

        $response = $this->get(route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame($encoderReturnValue, $response->getContent());
        $response->assertHeader('Content-Type', 'application/x-bittorrent');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $torrent->name . '.torrent"');
    }

    public function testDownloadWithUTF8TorrentFileNameWhichIncludesSpecialCharacters()
    {
        $this->withoutExceptionHandling();

        /** @var BdecodingService|MockObject $decoder */
        $decoder = $this->createMock(BdecodingService::class);
        /** @var BencodingService|MockObject $encoder */
        $encoder = $this->createMock(BencodingService::class);

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id, 'name' => 'čćš/đž%']);

        $storageReturnValue = 'something x264';
        Storage::shouldReceive('disk->get')->once()->with("torrents/{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $this->app->instance(BdecodingService::class, $decoder);

        $encoderReturnValue = 'something xyz';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(array_merge($decoderReturnValue, ['announce' => route('announce', ['passkey' => $this->user->passkey])])))
            ->willReturn($encoderReturnValue);

        $this->app->instance(BencodingService::class, $encoder);

        $response = $this->get(route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame($encoderReturnValue, $response->getContent());
        $response->assertHeader('Content-Type', 'application/x-bittorrent');
        $fileName = str_replace(['/', '\\'], '', $torrent->name . '.torrent');
        $contentDisposition = sprintf(
            '%s; filename="%s"' . "; filename*=utf-8''%s",
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'ccsdz.torrent',
            rawurlencode($fileName)
        );
        $response->assertHeader('Content-Disposition', $contentDisposition);
    }

    public function testUserGetsAPasskeyIfHeDidNotHaveItBefore()
    {
        $this->withoutExceptionHandling();

        $this->user->forceFill(['passkey' => null])->save();

        /** @var BdecodingService|MockObject $decoder */
        $decoder = $this->createMock(BdecodingService::class);
        /** @var BencodingService|MockObject $encoder */
        $encoder = $this->createMock(BencodingService::class);
        /** @var PasskeyService|MockObject $passkeyService */
        $passkeyService = $this->createMock(PasskeyService::class);

        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);

        $storageReturnValue = 'something x264';
        Storage::shouldReceive('disk->get')->once()->with("torrents/{$torrent->id}.torrent")->andReturn($storageReturnValue);

        $decoderReturnValue = ['info' => ['x' => 'y']];
        $decoder->expects($this->once())
            ->method('decode')
            ->with($this->equalTo($storageReturnValue))
            ->willReturn($decoderReturnValue);

        $this->app->instance(BdecodingService::class, $decoder);

        $passkey = 'test passkey';
        $passkeyService->expects($this->once())->method('generateUniquePasskey')->willReturn($passkey);
        $this->app->instance(PasskeyService::class, $passkeyService);

        $encoderReturnValue = 'something xyz';
        $encoder->expects($this->once())
            ->method('encode')
            ->with($this->equalTo(array_merge($decoderReturnValue, ['announce' => route('announce', ['passkey' => $passkey])])))
            ->willReturn($encoderReturnValue);

        $this->app->instance(BencodingService::class, $encoder);

        $response = $this->get(route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $this->assertSame($encoderReturnValue, $response->getContent());
        $response->assertHeader('Content-Type', 'application/x-bittorrent');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $torrent->name . '.torrent"');
        $user = $this->user->fresh();
        $this->assertSame($passkey, $user->passkey);
    }

    public function testDownloadWhenStorageThrowsAnException()
    {
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        Storage::shouldReceive('disk->get')->once()->with("torrents/{$torrent->id}.torrent")->andThrow(new FileNotFoundException());
        $response = $this->get(route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertSee(__('messages.torrent-file-missing.error-message'));
        $response->assertDontSee($torrent->name);
    }

    public function testGuestsCannotSeeTheTorrentsIndexPage()
    {
        $this->app->make('auth')->guard()->logout();
        $response = $this->get(route('torrents.index'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testGuestsCannotSeeTheTorrentPage()
    {
        $this->app->make('auth')->guard()->logout();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        $response = $this->get(route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testGuestsCannotDownloadTorrents()
    {
        $this->app->make('auth')->guard()->logout();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        $response = $this->get(route('torrents.download', $torrent));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testGuestsCannotUploadTorrents()
    {
        $this->app->make('auth')->guard()->logout();
        $response = $this->post(route('torrents.store'), $this->validParams());
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $this->assertSame(0, Torrent::count());
    }

    public function testTorrentFileIsRequired()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'torrent' => null,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('torrent');
        $this->assertSame(0, Torrent::count());
    }

    public function testTorrentMustBeAFile()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'torrent' => 'test string',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('torrent');
        $this->assertSame(0, Torrent::count());
    }

    public function testFileMustBeATorrent()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'torrent' => File::create('file.png'),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('torrent');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameIsRequired()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'name' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustContainAtLeast5Chars()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'name' => str_repeat('X', 4),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustBeLessThan256CharsLong()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'name' => str_repeat('X', 256),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustBeUnique()
    {
        $torrent = factory(Torrent::class)->create(['uploader_id' => $this->user->id]);
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'name' => $torrent->name,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Torrent::count());
    }

    public function testDescriptionIsRequired()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'description' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('description');
        $this->assertSame(0, Torrent::count());
    }

    /**
     * @param array $overrides
     *
     * @return array
     */
    private function validParams($overrides = []): array
    {
        return array_merge([
            'name'        => 'Test name',
            'description' => 'Test description',
            'torrent'     => File::create('file.torrent'),
        ], $overrides);
    }
}
