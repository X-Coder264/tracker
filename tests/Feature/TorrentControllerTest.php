<?php

namespace Tests\Feature;

use App\Http\Models\Torrent;
use App\Http\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

        $response = $this->get(route('torrent.index'));

        $response->assertSee($torrent->name);
        $response->assertSee($torrent->uploader->name);

        $response->assertStatus(200);
    }

    public function testCreate()
    {
        $response = $this->get(route('torrent.create'));

        $response->assertStatus(200);
    }
}
