<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use App\Http\Services\TorrentInfoService;
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
    }

    public function testCreate()
    {
        $response = $this->get(route('torrents.create'));

        $response->assertStatus(Response::HTTP_OK);
    }

    public function testShow()
    {
        $torrent = factory(Torrent::class)->create();

        $torrentInfo = $this->createMock(TorrentInfoService::class);
        $this->app->instance(TorrentInfoService::class, $torrentInfo);

        $returnValue = ['55.55 MB', 'Test.txt'];
        $torrentInfo->method('getTorrentFileNamesAndSizes')->willReturn($returnValue);

        $response = $this->get(route('torrents.show', $torrent));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('torrentFileNamesAndSizes', $returnValue);
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
            'name' => str_repeat("X", 4),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.create'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Torrent::count());
    }

    public function testNameMustBeLessThan256CharsLong()
    {
        $response = $this->from(route('torrents.create'))->post(route('torrents.store'), $this->validParams([
            'name' => str_repeat("X", 256),
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

    private function validParams($overrides = [])
    {
        return array_merge([
            'name' => 'Test name',
            'description' => 'Test description',
            'torrent' => File::create('file.torrent'),
        ], $overrides);
    }
}
