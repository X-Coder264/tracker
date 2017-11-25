<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
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

        $response->assertStatus(200);
    }

    public function testCreate()
    {
        $response = $this->get(route('torrents.create'));

        $response->assertStatus(200);
    }

    public function testShow()
    {
        $torrent = factory(Torrent::class)->create();

        $torrentInfoStub = $this->createMock(TorrentInfoService::class);
        $this->app->instance(TorrentInfoService::class, $torrentInfoStub);

        $returnValue = ['55.55 MB', 'Test.txt'];
        $torrentInfoStub->method('getTorrentFileNamesAndSizes')->willReturn($returnValue);

        $response = $this->get(route('torrents.show', $torrent));
        $response->assertStatus(200);
        $response->assertViewHas('torrentFileNamesAndSizes', $returnValue);
    }
}
