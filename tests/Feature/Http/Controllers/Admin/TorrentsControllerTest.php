<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

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
        $this->withExceptionHandling();

        $user = factory(User::class)->create();
        $torrents = factory(Torrent::class, 2)->create();
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index'));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(2, $jsonResponse['meta']['total']);
        $this->assertSame($torrents[0]->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($torrents[0]->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($torrents[0]->description, $jsonResponse['data'][0]['attributes']['description']);
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
        $this->assertSame($torrents[1]->description, $jsonResponse['data'][1]['attributes']['description']);
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

    public function testNameFilter()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();
        $torrents = factory(Torrent::class, 2)->create();
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index', ['filter[name]' => $torrents[1]->name]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($torrents[1]->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($torrents[1]->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($torrents[1]->description, $jsonResponse['data'][0]['attributes']['description']);
        $this->assertSame($torrents[1]->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $torrents[1]->uploader->id,
            (int) $jsonResponse['data'][0]['relationships']['uploader']['data']['id']
        );
        $this->assertSame(
            $torrents[1]->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $torrents[1]->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $torrents[1]->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testUploaderFilter()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();
        factory(Torrent::class, 2)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index', ['filter[uploader]' => $user->id]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($torrent->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($torrent->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($torrent->description, $jsonResponse['data'][0]['attributes']['description']);
        $this->assertSame($torrent->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $user->id,
            (int) $jsonResponse['data'][0]['relationships']['uploader']['data']['id']
        );
        $this->assertSame(
            $torrent->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $torrent->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $torrent->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testSlugFilter()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();
        $torrents = factory(Torrent::class, 2)->create();
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index', ['filter[slug]' => $torrents[1]->slug]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($torrents[1]->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($torrents[1]->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($torrents[1]->description, $jsonResponse['data'][0]['attributes']['description']);
        $this->assertSame($torrents[1]->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $torrents[1]->uploader->id,
            (int) $jsonResponse['data'][0]['relationships']['uploader']['data']['id']
        );
        $this->assertSame(
            $torrents[1]->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $torrents[1]->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $torrents[1]->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testMinimumSizeFilter()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();
        $firstTorrent = factory(Torrent::class)->create(['size' => 4194303]);
        $secondTorrent = factory(Torrent::class)->create(['size' => 4194305]);
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index', ['filter[minimumSize]' => 4]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($secondTorrent->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($secondTorrent->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($secondTorrent->description, $jsonResponse['data'][0]['attributes']['description']);
        $this->assertSame($secondTorrent->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $secondTorrent->uploader->id,
            (int) $jsonResponse['data'][0]['relationships']['uploader']['data']['id']
        );
        $this->assertSame(
            $secondTorrent->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $secondTorrent->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $secondTorrent->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }

    public function testMaximumSizeFilter()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();
        $firstTorrent = factory(Torrent::class)->create(['size' => 4194303]);
        $secondTorrent = factory(Torrent::class)->create(['size' => 4194305]);
        $this->actingAs($user);
        $response = $this->makeRequest('GET', route('admin.torrents.index', ['filter[maximumSize]' => 4]));
        $jsonResponse = $response->getJsonResponse();

        $this->assertSame(1, $jsonResponse['meta']['total']);
        $this->assertSame($firstTorrent->name, $jsonResponse['data'][0]['attributes']['name']);
        $this->assertSame($firstTorrent->size, $jsonResponse['data'][0]['attributes']['size']);
        $this->assertSame($firstTorrent->description, $jsonResponse['data'][0]['attributes']['description']);
        $this->assertSame($firstTorrent->slug, $jsonResponse['data'][0]['attributes']['slug']);
        $this->assertSame(
            $firstTorrent->uploader->id,
            (int) $jsonResponse['data'][0]['relationships']['uploader']['data']['id']
        );
        $this->assertSame(
            $firstTorrent->created_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['created-at']
        );
        $this->assertSame(
            $firstTorrent->updated_at->format(Carbon::W3C),
            $jsonResponse['data'][0]['attributes']['updated-at']
        );
        $this->assertSame(route('admin.torrents.read', $firstTorrent->id), $jsonResponse['data'][0]['links']['self']);
        $this->assertCount(1, $jsonResponse['data']);
    }
}
