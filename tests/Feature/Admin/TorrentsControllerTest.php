<?php

namespace Tests\Feature\Admin;

use App\Http\Models\User;
use Tests\AdminApiTestCase;
use App\Http\Models\Torrent;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentsControllerTest extends AdminApiTestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $user = factory(User::class)->create();
        $torrents = factory(Torrent::class, 2)->create();
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index'));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(2, $jsonResponse['meta']['total']);
        $this->assertSame($torrents[0]->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($torrents[0]->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($torrents[0]->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $torrents[0]->uploader->id,
            (int) $jsonResponse['data'][0]['relationships']['uploader']['data']['id']
        );
        $this->assertSame(
            $torrents[0]->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $torrents[0]->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $torrents[0]->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertSame($torrents[1]->name, $jsonResponse['data'][1]['attributes']['name']);
        $this->assertSame($torrents[1]->size, $jsonResponse['data'][1]['attributes']['size']);
        $this->assertSame($torrents[1]->slug, $jsonResponse['data'][1]['attributes']['slug']);
        $this->assertSame(
            $torrents[1]->created_at->format(Carbon::W3C),
            $jsonResponse['data'][1]['attributes']['created-at']
        );
        $this->assertSame(
            $torrents[1]->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][1]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $torrents[1]->id), $jsonResponse['data'][1]['links']['self']);
    }
}
