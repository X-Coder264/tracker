<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\RSS;

use Tests\TestCase;
use App\Models\User;
use App\Models\Torrent;
use App\Models\TorrentCategory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentFeedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testShowWithoutCategories(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $torrents = factory(Torrent::class, 2)->states(['alive', 'v1'])->create();

        factory(Torrent::class)->states(['dead', 'v1'])->create();

        $this->actingAs($user);

        $url = route('torrents.rss', ['passkey' => $user->passkey]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = json_decode(json_encode(simplexml_load_string($response->getContent())), true);

        $this->assertSame('2.0', $xml['@attributes']['version']);
        $this->assertSame($url, $xml['channel']['link']);
        $this->assertNotEmpty($xml['channel']['title']);
        $this->assertNotEmpty($xml['channel']['description']);
        $this->assertSame(2, count($xml['channel']['item']));
        $this->assertSame($torrents[1]->name, $xml['channel']['item'][0]['title']);
        $this->assertSame($torrents[0]->name, $xml['channel']['item'][1]['title']);
        $this->assertSame($torrents[1]->created_at->format('D, d M Y H:i:s O'), $xml['channel']['item'][0]['pubDate']);
        $this->assertSame($torrents[0]->created_at->format('D, d M Y H:i:s O'), $xml['channel']['item'][1]['pubDate']);
        $this->assertSame($torrents[1]->infoHashes->first()->info_hash, $xml['channel']['item'][0]['guid']);
        $this->assertSame($torrents[0]->infoHashes->first()->info_hash, $xml['channel']['item'][1]['guid']);
        $this->assertSame(
            route('torrents.download', ['torrent' => $torrents[1], 'passkey' => $user->passkey]),
            $xml['channel']['item'][0]['enclosure']['@attributes']['url']
        );
        $this->assertSame(
            route('torrents.download', ['torrent' => $torrents[0], 'passkey' => $user->passkey]),
            $xml['channel']['item'][1]['enclosure']['@attributes']['url']
        );
        $this->assertSame('application/x-bittorrent', $xml['channel']['item'][0]['enclosure']['@attributes']['type']);
        $this->assertSame('application/x-bittorrent', $xml['channel']['item'][1]['enclosure']['@attributes']['type']);

        /** @var Repository $cache */
        $cache = $this->app->make(Repository::class);
        $this->assertTrue($cache->has(md5('torrents.rss-feed')));
        $cachedResponseContent = $cache->get(md5('torrents.rss-feed'));

        $xml = json_decode(json_encode(simplexml_load_string($cachedResponseContent)), true);

        $this->assertSame('2.0', $xml['@attributes']['version']);
        $this->assertSame($url, $xml['channel']['link']);
        $this->assertNotEmpty($xml['channel']['title']);
        $this->assertNotEmpty($xml['channel']['description']);
        $this->assertSame(2, count($xml['channel']['item']));
        $this->assertSame($torrents[1]->name, $xml['channel']['item'][0]['title']);
        $this->assertSame($torrents[0]->name, $xml['channel']['item'][1]['title']);
        $this->assertSame($torrents[1]->created_at->format('D, d M Y H:i:s O'), $xml['channel']['item'][0]['pubDate']);
        $this->assertSame($torrents[0]->created_at->format('D, d M Y H:i:s O'), $xml['channel']['item'][1]['pubDate']);
        $this->assertSame($torrents[1]->infoHashes->first()->info_hash, $xml['channel']['item'][0]['guid']);
        $this->assertSame($torrents[0]->infoHashes->first()->info_hash, $xml['channel']['item'][1]['guid']);
        $this->assertSame(
            route('torrents.download', ['torrent' => $torrents[1], 'passkey' => $user->passkey]),
            $xml['channel']['item'][0]['enclosure']['@attributes']['url']
        );
        $this->assertSame(
            route('torrents.download', ['torrent' => $torrents[0], 'passkey' => $user->passkey]),
            $xml['channel']['item'][1]['enclosure']['@attributes']['url']
        );
        $this->assertSame('application/x-bittorrent', $xml['channel']['item'][0]['enclosure']['@attributes']['type']);
        $this->assertSame('application/x-bittorrent', $xml['channel']['item'][1]['enclosure']['@attributes']['type']);
    }

    public function testShowWithCategories(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $torrentCategories = factory(TorrentCategory::class, 3)->create();

        $torrent = factory(Torrent::class)->states(['alive', 'v1'])->create(['category_id' => $torrentCategories[2]->id]);
        factory(Torrent::class)->states(['alive', 'v1'])->create(['category_id' => $torrentCategories[0]->id]);
        factory(Torrent::class)->states(['dead', 'v1'])->create(['category_id' => $torrentCategories[1]->id]);

        $categories = sprintf('%d,%d', $torrentCategories[1]->id, $torrentCategories[2]->id);

        $this->actingAs($user);

        $url = route('torrents.rss', ['passkey' => $user->passkey, 'categories' => $categories]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = json_decode(json_encode(simplexml_load_string($response->getContent())), true);

        $this->assertSame('2.0', $xml['@attributes']['version']);
        $this->assertSame($url, $xml['channel']['link']);
        $this->assertNotEmpty($xml['channel']['title']);
        $this->assertNotEmpty($xml['channel']['description']);
        $this->assertSame($torrent->name, $xml['channel']['item']['title']);
        $this->assertSame($torrent->created_at->format('D, d M Y H:i:s O'), $xml['channel']['item']['pubDate']);
        $this->assertSame($torrent->infoHashes->first()->info_hash, $xml['channel']['item']['guid']);
        $this->assertSame(
            route('torrents.download', ['torrent' => $torrent, 'passkey' => $user->passkey]),
            $xml['channel']['item']['enclosure']['@attributes']['url']
        );
        $this->assertSame('application/x-bittorrent', $xml['channel']['item']['enclosure']['@attributes']['type']);

        /** @var Repository $cache */
        $cache = $this->app->make(Repository::class);
        $cacheKey = sprintf('torrents.rss-feed.%s', implode(',', [$torrentCategories[1]->id, $torrentCategories[2]->id]));
        $this->assertTrue($cache->has(md5($cacheKey)));
        $cachedResponseContent = $cache->get(md5($cacheKey));

        $xml = json_decode(json_encode(simplexml_load_string($cachedResponseContent)), true);

        $this->assertSame('2.0', $xml['@attributes']['version']);
        $this->assertSame($url, $xml['channel']['link']);
        $this->assertNotEmpty($xml['channel']['title']);
        $this->assertNotEmpty($xml['channel']['description']);
        $this->assertSame($torrent->name, $xml['channel']['item']['title']);
        $this->assertSame($torrent->created_at->format('D, d M Y H:i:s O'), $xml['channel']['item']['pubDate']);
        $this->assertSame($torrent->infoHashes->first()->info_hash, $xml['channel']['item']['guid']);
        $this->assertSame(
            route('torrents.download', ['torrent' => $torrent, 'passkey' => $user->passkey]),
            $xml['channel']['item']['enclosure']['@attributes']['url']
        );
        $this->assertSame('application/x-bittorrent', $xml['channel']['item']['enclosure']['@attributes']['type']);
    }

    public function testFeedCannotBeAccessedWithoutAValidPasskey(): void
    {
        $response = $this->get(route('torrents.rss', ['passkey' => 'does-not-exist']));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
