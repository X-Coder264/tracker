<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use App\Http\Models\TorrentComment;
use App\Http\Services\TorrentInfoService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();
        $user = factory(User::class)->create();
        $this->actingAs($user);
    }

    public function testIndex()
    {
        $torrent = factory(Torrent::class)->create();

        $response = $this->get(route('torrents.index'));

        $response->assertSee($torrent->name);
        $response->assertSee($torrent->uploader->name);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.index');
        $response->assertViewHas(['torrents', 'timezone']);
    }

    public function testCreate()
    {
        $response = $this->get(route('torrents.create'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.create');
    }

    public function testShow()
    {
        $torrent = factory(Torrent::class)->create();
        $torrentComment = factory(TorrentComment::class)->create(
            ['torrent_id' => $torrent->id, 'user_id' => $torrent->uploader_id]
        );

        $torrentInfo = $this->createMock(TorrentInfoService::class);
        $this->app->instance(TorrentInfoService::class, $torrentInfo);

        $returnValue = ['55.55 MB', 'Test.txt'];
        $torrentInfo->method('getTorrentFileNamesAndSizes')->willReturn($returnValue);

        $response = $this->get(route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrents.show');
        $response->assertViewHas('torrent');
        $response->assertViewHas('numberOfPeers');
        $response->assertViewHas('torrentFileNamesAndSizes', $returnValue);
        $response->assertViewHas('torrentComments');
        $response->assertViewHas('timezone');
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->original->torrentComments);
        $response->assertSee($torrentComment->comment);
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
        $torrent = factory(Torrent::class)->create();
        $response = $this->get(route('torrents.show', $torrent));
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
        $torrent = factory(Torrent::class)->create();
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
            'name' => 'Test name',
            'description' => 'Test description',
            'torrent' => File::create('file.torrent'),
        ], $overrides);
    }
}
