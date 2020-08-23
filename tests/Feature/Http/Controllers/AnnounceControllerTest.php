<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Peer;
use App\Models\PeerIP;
use App\Models\PeerVersion;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\TorrentInfoHash;
use App\Models\User;
use App\Presenters\Announce\User as AnnounceUserModel;
use App\Services\Bdecoder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

final class AnnounceControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testV1PeerStartsLeechingWithNoOtherPeersPresentOnTheTorrent()
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 0, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id . '.peers', 'test', 10);

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali3000e12:min intervali1200e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame($peer->created_at->format('Y-m-d H:is'), $peer->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peer->updated_at));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame($peerVersion->created_at->format('Y-m-d H:is'), $peerVersion->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peerVersion->updated_at));
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());

        $this->assertFalse($cache->has('user.' . $user->id . '.peers'));
    }

    public function testV2PeerStartsLeechingWithNoOtherPeersPresentOnTheTorrent(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 0, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id, 'version' => 2]);
        $user = factory(User::class)->create();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali3000e12:min intervali1200e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame($peer->created_at->format('Y-m-d H:is'), $peer->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peer->updated_at));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(2, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame($peerVersion->created_at->format('Y-m-d H:is'), $peerVersion->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peerVersion->updated_at));
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testV2PeerStartsLeechingWithNoOtherV2PeersPresentOnTheTorrent(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $v1InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d976';
        $v2InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 1, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v1InfoHash, 'torrent_id' => $torrent->id, 'version' => 1]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v2InfoHash, 'torrent_id' => $torrent->id, 'version' => 2]);
        $user = factory(User::class)->create();
        $peer = factory(Peer::class)->states('seeder')->create(['torrent_id' => $torrent->id]);
        factory(PeerIP::class)->create(['peer_id' => $peer->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]);
        $peer->versions()->save(new PeerVersion(['version' => 1]));

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($v2InfoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei1e10:incompletei0e8:intervali3000e12:min intervali1200e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(2, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertSame($peer->created_at->format('Y-m-d H:is'), $peer->updated_at->format('Y-m-d H:is'));
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peer->updated_at));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertSame(2, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(2, PeerVersion::count());
        $peerVersion = PeerVersion::latest('id')->firstOrFail();
        $this->assertSame(2, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame($peerVersion->created_at->format('Y-m-d H:is'), $peerVersion->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peerVersion->updated_at));
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testV1PeerStartsLeechingWithNoOtherV1PeersPresentOnTheTorrent(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $v1InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d976';
        $v2InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 1, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v1InfoHash, 'torrent_id' => $torrent->id, 'version' => 1]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v2InfoHash, 'torrent_id' => $torrent->id, 'version' => 2]);
        $user = factory(User::class)->create();
        $peer = factory(Peer::class)->states('seeder')->create(['torrent_id' => $torrent->id]);
        factory(PeerIP::class)->create(
            ['peer_id' => $peer->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );
        $peer->versions()->save(new PeerVersion(['version' => 2]));

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($v1InfoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei1e10:incompletei0e8:intervali3000e12:min intervali1200e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(2, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame($peer->created_at->format('Y-m-d H:is'), $peer->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peer->updated_at));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertSame(2, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(2, PeerVersion::count());
        $peerVersion = PeerVersion::latest('id')->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame($peerVersion->created_at->format('Y-m-d H:is'), $peerVersion->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peerVersion->updated_at));
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testV1PeerStartsLeechingWithOtherPeersPresentOnTheTorrent(): void
    {
        $this->withoutExceptionHandling();

        $v1InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d976';
        $v2InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $peerIdOne = '2d7142333345302d64354e334474384672517777';
        $peerIdTwo = '2d7142333345302d64354e334474384672517778';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 1, 'leechers' => 1]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v1InfoHash, 'torrent_id' => $torrent->id]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v2InfoHash, 'torrent_id' => $torrent->id, 'version' => 2]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 0, 'peer_id' => $peerIdOne]
        );
        factory(PeerVersion::class)->create(['peer_id' => $peerOne->id, 'version' => 2]);
        $peerOneIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerOne->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );
        $peerTwo = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 500, 'peer_id' => $peerIdTwo]
        );
        factory(PeerVersion::class)->create(['peer_id' => $peerTwo->id, 'version' => 2]);
        $peerTwoIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerTwo->id, 'ip' => '98.165.38.52', 'is_ipv6' => false, 'port' => 55556]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($v1InfoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note 1: because we use the "inRandomOrder" method in the getPeersForTorrent method there can be two possible responses
        // Note 2: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponseOne = [
            'complete' => 1,
            'incomplete' => 1,
            'interval' => 2400,
            'min interval' => 60,
            'peers' => bin2hex(inet_pton($peerOneIP->ip) . pack('n*', $peerOneIP->port) . inet_pton($peerTwoIP->ip) . pack('n*', $peerTwoIP->port)),
            'peers6' => '',
        ];
        $expectedResponseTwo = [
            'complete' => 1,
            'incomplete' => 1,
            'interval' => 2400,
            'min interval' => 60,
            'peers' => bin2hex(inet_pton($peerTwoIP->ip) . pack('n*', $peerTwoIP->port) . inet_pton($peerOneIP->ip) . pack('n*', $peerOneIP->port)),
            'peers6' => '',
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        $this->assertThat(
            $responseContent,
            $this->logicalOr(
                $this->equalTo($expectedResponseOne),
                $this->equalTo($expectedResponseTwo)
            )
        );
        $this->assertSame(3, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, Snatch::count());
        $this->assertSame(5, PeerVersion::count());
        $peerVersion = PeerVersion::latest('id')->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(2, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testStartSeedingWithNoOtherPeersPresentOnTheTorrent()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 0, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id . '.peers', 'test', 10);

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertSame(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());

        $this->assertFalse($cache->has('user.' . $user->id . '.peers'));
    }

    public function testSeederDroppingOutOfTheSwarm()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 1, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id . '.peers', 'test', 10);

        $peer = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'left'       => 0,
                'peer_id'    => $peerId,
                'user_id'    => $user->id,
                'uploaded'   => 2000,
                'downloaded' => $torrent->getRawOriginal('size'),
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $peer->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 0,
                'seed_time'       => 500,
                'leech_time'      => 1200,
                'times_announced' => 2,
                'uploaded'        => 2000,
                'downloaded'      => $torrent->getRawOriginal('size'),
                'finished_at'     => CarbonImmutable::yesterday(),
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'stopped',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 3000,
                    'left'       => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, PeerVersion::count());
        $this->assertSame(0, PeerIP::count());
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(3000, (int) $freshSnatch->getRawOriginal('uploaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $freshSnatch->getRawOriginal('downloaded'));
        $this->assertSame(0, (int) $freshSnatch->getRawOriginal('left'));
        $this->assertGreaterThanOrEqual(2900, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertSame(1200, (int) $freshSnatch->getRawOriginal('leech_time'));
        $this->assertSame(3, (int) $freshSnatch->times_announced);
        $this->assertSame($snatch->finished_at->toDateTimeString(), $freshSnatch->finished_at->toDateTimeString());
        $this->assertSame($userAgent, $freshSnatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 1000, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());

        $this->assertFalse($cache->has('user.' . $user->id . '.peers'));
    }

    public function testLeecherDroppingOutOfTheSwarm()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 0,
                'leechers' => 1,
                'size'     => 3000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id . '.peers', 'test', 10);

        $peer = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'left'       => 300,
                'peer_id'    => $peerId,
                'user_id'    => $user->id,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $peer->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 2000,
                'seed_time'       => 0,
                'leech_time'      => 1200,
                'times_announced' => 2,
                'uploaded'        => 2000,
                'downloaded'      => 1000,
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'stopped',
                    'port'       => $port,
                    'uploaded'   => 3000,
                    'downloaded' => 2200,
                    'left'       => 800,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, PeerIP::count());
        $this->assertSame(0, PeerVersion::count());
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(3000, (int) $freshSnatch->getRawOriginal('uploaded'));
        $this->assertSame(2200, (int) $freshSnatch->getRawOriginal('downloaded'));
        $this->assertSame(800, (int) $freshSnatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertGreaterThanOrEqual(3600, (int) $freshSnatch->getRawOriginal('leech_time'));
        $this->assertSame(3, (int) $freshSnatch->times_announced);
        $this->assertNull($freshSnatch->finished_at);
        $this->assertSame($userAgent, $freshSnatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 1000, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded') + 1200, (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());

        $this->assertFalse($cache->has('user.' . $user->id . '.peers'));
    }

    public function testLeecherCompletingTheTorrent()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $peerIdOne = '2d7142333345302d64354e334474384672517777';
        $peerIdTwo = '2d7142333345302d64354e334474384672517778';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 1,
                'leechers' => 2,
                'size'     => 5000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id . '.peers', 'test', 10);

        $peerOne = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 0, 'peer_id' => $peerIdOne]
        );
        factory(PeerIP::class)->create(
            ['peer_id' => $peerOne->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );
        $peerTwo = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 400, 'peer_id' => $peerIdTwo]
        );
        $peerTwoIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerTwo->id, 'ip' => '98.165.38.52', 'is_ipv6' => false, 'port' => 55556]
        );

        $leecher = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'left'       => 500,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $leecher->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 4000,
                'seed_time'       => 0,
                'leech_time'      => 1000,
                'times_announced' => 2,
                'uploaded'        => 2000,
                'downloaded'      => 1000,
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'completed',
                    'port'       => $port,
                    'uploaded'   => 2000,
                    'downloaded' => 5000,
                    'left'       => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponse = [
            'complete'     => 1,
            'incomplete'   => 1,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerTwoIP->ip) . pack('n*', $peerTwoIP->port)),
            'peers6'       => '',
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = $leecher->fresh();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2000, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(5000, (int) $peer->getRawOriginal('downloaded'));
        $this->assertSame(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(3, PeerVersion::count());
        $peerVersion = PeerVersion::latest('id')->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $snatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(2000, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(5000, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('seed_time'));
        $this->assertGreaterThanOrEqual(3400, (int) $snatch->getRawOriginal('leech_time'));
        $this->assertSame(3, (int) $snatch->times_announced);
        $this->assertNotNull($snatch->finished_at);
        $this->assertLessThanOrEqual(10, CarbonImmutable::now()->diffInSeconds($snatch->finished_at));
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded') + 4000, (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());

        $this->assertFalse($cache->has('user.' . $user->id . '.peers'));
    }

    public function testSeederContinuingToSeed()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 1,
                'leechers' => 0,
                'size'     => 1000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $seeder = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'left'       => 0,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $seeder->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 0,
                'seed_time'       => 3000,
                'leech_time'      => 1000,
                'times_announced' => 5,
                'uploaded'        => 2000,
                'downloaded'      => 1000,
                'finished_at'     => CarbonImmutable::now()->subMinutes(200),
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 3000,
                    'downloaded' => 1000,
                    'left'       => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(3000, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(1000, (int) $peer->getRawOriginal('downloaded'));
        $this->assertSame(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(3000, (int) $freshSnatch->getRawOriginal('uploaded'));
        $this->assertSame(1000, (int) $freshSnatch->getRawOriginal('downloaded'));
        $this->assertSame(0, (int) $freshSnatch->getRawOriginal('left'));
        $this->assertGreaterThanOrEqual(3400, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertSame($snatch->getRawOriginal('leech_time'), (int) $freshSnatch->getRawOriginal('leech_time'));
        $this->assertSame(6, (int) $freshSnatch->times_announced);
        $this->assertNotNull($freshSnatch->finished_at);
        $this->assertSame($snatch->finished_at->toDateTimeString(), $freshSnatch->finished_at->toDateTimeString());
        $this->assertSame($userAgent, $freshSnatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 1000, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testLeecherContinuingToLeech()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 0,
                'leechers' => 1,
                'size'     => 5000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $leecher = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'left'       => 500,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $leecher->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 4000,
                'seed_time'       => 0,
                'leech_time'      => 1000,
                'times_announced' => 5,
                'uploaded'        => 2000,
                'downloaded'      => 1000,
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 2500,
                    'downloaded' => 1800,
                    'left'       => 3200,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(2500, (int) $freshSnatch->getRawOriginal('uploaded'));
        $this->assertSame(1800, (int) $freshSnatch->getRawOriginal('downloaded'));
        $this->assertSame(3200, (int) $freshSnatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertGreaterThanOrEqual(3400, (int) $freshSnatch->getRawOriginal('leech_time'));
        $this->assertSame(6, (int) $freshSnatch->times_announced);
        $this->assertNull($freshSnatch->finished_at);
        $this->assertSame($userAgent, $freshSnatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 500, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded') + 800, (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testRecordingOfTheTrafficWhenAnnouncingOnTheV2HashImmediatelyAfterTheV1HashAnnounce(): void
    {
        $this->withoutExceptionHandling();

        $v1InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d976';
        $v2InfoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 1,
                'leechers' => 0,
                'size'     => 1000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $v1InfoHash, 'torrent_id' => $torrent->id]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $v2InfoHash, 'torrent_id' => $torrent->id, 'version' => 2]);
        $user = factory(User::class)->create();
        $seeder = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'left'       => 0,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subSeconds(1),
            ]
        );

        $this->app->make(ConnectionInterface::class)->table('peers_version')
            ->where('peer_id', '=', $seeder->id)
            ->where('version', '=', 1)
            ->update(['updated_at' => CarbonImmutable::now()->subSeconds(1)]);
        factory(PeerVersion::class)->states('v2')->create(
            ['peer_id' => $seeder->id, 'updated_at' => CarbonImmutable::now()->subMinutes(40)]
        );
        factory(PeerIP::class)->create(['peer_id' => $seeder->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 0,
                'seed_time'       => 3000,
                'leech_time'      => 1000,
                'times_announced' => 5,
                'uploaded'        => 2000,
                'downloaded'      => 1000,
                'finished_at'     => CarbonImmutable::now()->subMinutes(200),
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($v2InfoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 2010,
                    'downloaded' => 0,
                    'left'       => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2010, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(1000, (int) $peer->getRawOriginal('downloaded'));
        $this->assertSame(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(2, PeerVersion::count());
        $peerVersion = PeerVersion::latest('id')->firstOrFail();
        $this->assertSame(2, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(2010, (int) $freshSnatch->getRawOriginal('uploaded'));
        $this->assertSame(1000, (int) $freshSnatch->getRawOriginal('downloaded'));
        $this->assertSame(0, (int) $freshSnatch->getRawOriginal('left'));
        $this->assertGreaterThan(3000, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertLessThanOrEqual(3002, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertSame($snatch->getRawOriginal('leech_time'), (int) $freshSnatch->getRawOriginal('leech_time'));
        $this->assertSame(6, (int) $freshSnatch->times_announced);
        $this->assertNotNull($freshSnatch->finished_at);
        $this->assertSame($snatch->finished_at->toDateTimeString(), $freshSnatch->finished_at->toDateTimeString());
        $this->assertSame($userAgent, $freshSnatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 10, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testEventStartedWithThePeerAlreadyInTheDB()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 0,
                'leechers' => 1,
                'size'     => 5000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $leecher = factory(Peer::class)->states('v1')->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'left'       => 4000,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => CarbonImmutable::now()->subMinutes(300),
                'updated_at' => CarbonImmutable::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peer_id' => $leecher->id, 'ip' => $IP, 'is_ipv6' => false, 'port' => $port]);

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 2500,
                    'downloaded' => 1800,
                    'left'       => 3200,
                    'event'      => 'started',
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 500, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded') + 800, (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testStartingToLeechAPreviouslySnatchedTorrentUpdatesTheExistingSnatch()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 0,
                'leechers' => 0,
                'size'     => 5000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'      => $torrent->id,
                'user_id'         => $user->id,
                'left'            => 0,
                'seed_time'       => 0,
                'leech_time'      => 1000,
                'times_announced' => 5,
                'uploaded'        => 2000,
                'downloaded'      => 5000,
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 2500,
                    'downloaded' => 1800,
                    'left'       => 3200,
                    'event'      => 'started',
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(4500, (int) $freshSnatch->getRawOriginal('uploaded'));
        $this->assertSame(6800, (int) $freshSnatch->getRawOriginal('downloaded'));
        $this->assertSame(3200, (int) $freshSnatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $freshSnatch->getRawOriginal('seed_time'));
        $this->assertSame(1000, (int) $freshSnatch->getRawOriginal('leech_time'));
        $this->assertSame(6, (int) $freshSnatch->times_announced);
        $this->assertNull($freshSnatch->finished_at);
        $this->assertSame($userAgent, $freshSnatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 2500, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded') + 1800, (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testNoEventWithTheLeecherNotPresentInTheDB()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 0,
                'leechers' => 0,
                'size'     => 5000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 2500,
                    'downloaded' => 1800,
                    'left'       => 3200,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 2500, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded') + 1800, (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testNoEventWithTheSeederNotPresentInTheDB()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(
            [
                'seeders'  => 0,
                'leechers' => 0,
                'size'     => 5000,
            ]
        );
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'port'       => $port,
                    'uploaded'   => 2500,
                    'downloaded' => 0,
                    'left'       => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali2400e12:min intervali60e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertSame(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded') + 2500, (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testPeerWithIPv6AddressBEP7()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv6 = '2001::53aa:64c:0:7f83:bc43:dec9';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 2, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->states('v1')->create(['torrent_id' => $torrent->id, 'left' => 0]);
        $peerOneIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerOne->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );
        $peerTwo = factory(Peer::class)->states('v1')->create(['torrent_id' => $torrent->id, 'left' => 0]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peer_id' => $peerTwo->id,
                'ip'      => '2001::53aa:64c:0:7f83:bc43:ded9',
                'is_ipv6' => true,
                'port'    => 55556,
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IPv6,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponse = [
            'complete'     => 2,
            'incomplete'   => 0,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerOneIP->ip) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->ip) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IPv6, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertTrue((bool) $peerIP->is_ipv6);
        $this->assertSame(3, PeerVersion::count());
        $peerVersion = PeerVersion::where('peer_id', '=', $peer->id)->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testPeerWithIPv6Address()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv6 = '2001::53aa:64c:0:7f83:bc43:dec9';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 2, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->states('v1')->create(['torrent_id' => $torrent->id, 'left' => 0]);
        $peerOneIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerOne->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );
        $peerTwo = factory(Peer::class)->states('v1')->create(['torrent_id' => $torrent->id, 'left' => 0]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peer_id' => $peerTwo->id,
                'ip'      => '2001::53aa:64c:0:7f83:bc43:ded9',
                'is_ipv6' => true,
                'port'    => 55556,
            ]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IPv6,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponse = [
            'complete'     => 2,
            'incomplete'   => 0,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerOneIP->ip) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->ip) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IPv6, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertTrue((bool) $peerIP->is_ipv6);
        $this->assertSame(1, Snatch::count());
        $this->assertSame(3, PeerVersion::count());
        $peerVersion = PeerVersion::where('peer_id', '=', $peer->id)->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testNonCompactResponse()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $peerIdOne = '2d7142333345302d64354e334474384672517777';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 1, 'leechers' => 1]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 0, 'peer_id' => $peerIdOne]
        );
        $peerOneIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerOne->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                    'compact'    => 0,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponse = [
            'complete'     => 1,
            'incomplete'   => 1,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => [
                [
                    'ip'      => $peerOneIP->ip,
                    'peer id' => hex2bin($peerIdOne),
                    'port'    => $peerOneIP->port,
                ],
            ],
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($responseContent);
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(2, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(2, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(2, PeerVersion::count());
        $peerVersion = PeerVersion::where('peer_id', '=', $peer->id)->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(2, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));
        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);

        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testNumWantParameterIsRespected()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $peerIdOne = '2d7142333345302d64354e334474384672517777';
        $peerIdTwo = '2d7142333345302d64354e334474384672517778';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 1, 'leechers' => 1]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 0, 'peer_id' => $peerIdOne]
        );
        $peerOneIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerOne->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );
        $peerTwo = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 1000, 'peer_id' => $peerIdTwo]
        );
        $peerTwoIP = factory(PeerIP::class)->create(
            ['peer_id' => $peerTwo->id, 'ip' => '98.165.38.52', 'is_ipv6' => false, 'port' => 55556]
        );

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                    'numwant'    => 1,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note 1: because we use the "inRandomOrder" method in the getPeersForTorrent method there can be two possible responses
        // Note 2: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponseOne = [
            'complete'     => 1,
            'incomplete'   => 1,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerOneIP->ip) . pack('n*', $peerOneIP->port)),
            'peers6'       => '',
        ];
        $expectedResponseTwo = [
            'complete'     => 1,
            'incomplete'   => 1,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerTwoIP->ip) . pack('n*', $peerTwoIP->port)),
            'peers6'       => '',
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        $this->assertThat(
            $responseContent,
            $this->logicalOr(
                $this->equalTo($expectedResponseOne),
                $this->equalTo($expectedResponseTwo)
            )
        );
        $this->assertSame(3, Peer::count());
        $peer = Peer::latest('id')->firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::latest('id')->firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(3, PeerVersion::count());
        $peerVersion = PeerVersion::where('peer_id', '=', $peer->id)->firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(2, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());
    }

    public function testPeerKeyGetsWrittenToTheDatabase(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $key = bin2hex(random_bytes(64));
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 0, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id . '.peers', 'test', 10);

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                    'key'        => $key,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd8:completei0e10:incompletei0e8:intervali3000e12:min intervali1200e5:peers0:6:peers60:e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $peer = Peer::firstOrFail();
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($key, $peer->key);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getRawOriginal('downloaded'));
        $this->assertInstanceOf(CarbonImmutable::class, $peer->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $peer->updated_at);
        $this->assertSame($peer->created_at->format('Y-m-d H:is'), $peer->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peer->updated_at));
        $this->assertGreaterThan(0, $peer->left);
        $this->assertSame($userAgent, $peer->user_agent);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::firstOrFail();
        $this->assertSame($IP, $peerIP->ip);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->is_ipv6);
        $this->assertSame(1, PeerVersion::count());
        $peerVersion = PeerVersion::firstOrFail();
        $this->assertSame(1, $peerVersion->version);
        $this->assertSame($peer->id, $peerVersion->peer_id);
        $this->assertSame($peerVersion->created_at->format('Y-m-d H:is'), $peerVersion->updated_at->format('Y-m-d H:is'));
        $this->assertLessThanOrEqual(2, CarbonImmutable::now()->diffInSeconds($peerVersion->updated_at));
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::firstOrFail();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getRawOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getRawOriginal('downloaded'));
        $this->assertSame($torrent->getRawOriginal('size'), (int) $snatch->getRawOriginal('left'));
        $this->assertSame(0, (int) $snatch->seed_time);
        $this->assertSame(0, (int) $snatch->leech_time);
        $this->assertSame(1, (int) $snatch->times_announced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->user_agent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getRawOriginal('uploaded'), (int) $freshUser->getRawOriginal('uploaded'));
        $this->assertSame($user->getRawOriginal('downloaded'), (int) $freshUser->getRawOriginal('downloaded'));

        /** @var AnnounceUserModel $cachedUser */
        $cachedUser = $this->app->make(Repository::class)->get('user.' . $freshUser->passkey);
        $this->assertInstanceOf(AnnounceUserModel::class, $cachedUser);
        $this->assertSame((int) $freshUser->getRawOriginal('uploaded'), $cachedUser->getUploaded());
        $this->assertSame((int) $freshUser->getRawOriginal('downloaded'), $cachedUser->getDownloaded());

        $this->assertFalse($cache->has('user.' . $user->id . '.peers'));
    }

    public function testPeerSendsADifferentKeyThanTheOneWeHaveInTheDatabaseOnAStoppedEvent(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $key = bin2hex(random_bytes(64));
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 0, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $peer = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 0, 'peer_id' => $peerId, 'key' => $key]
        );
        factory(PeerIP::class)->create(
            ['peer_id' => $peer->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );

        $this->assertSame(1, Peer::count());
        $this->assertSame(1, PeerIP::count());
        $this->assertSame(1, PeerVersion::count());
        $this->assertSame(0, Snatch::count());

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'stopped',
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getRawOriginal('size'),
                    'key'        => $key . 'foo',
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd14:failure reason16:Invalid peer_id.e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $this->assertSame(1, PeerIP::count());
        $this->assertSame(1, PeerVersion::count());
        $this->assertSame(0, Snatch::count());

        $freshPeer = Peer::firstOrFail();
        $this->assertSame($peer->uploaded, $freshPeer->uploaded);
        $this->assertSame($peer->downloaded, $freshPeer->downloaded);
        $this->assertSame($peer->left, $freshPeer->left);
    }

    public function testPeerSendsADifferentKeyThanTheOneWeHaveInTheDatabaseOnACompletedEvent(): void
    {
        $this->withoutExceptionHandling();

        $this->app->make(ConfigRepository::class)->set('tracker.announce_interval', 50);
        $this->app->make(ConfigRepository::class)->set('tracker.min_announce_interval', 20);

        $key = bin2hex(random_bytes(64));
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['seeders' => 0, 'leechers' => 0]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);
        $user = factory(User::class)->create();

        $peer = factory(Peer::class)->states('v1')->create(
            ['torrent_id' => $torrent->id, 'left' => 500, 'peer_id' => $peerId, 'key' => $key]
        );
        factory(PeerIP::class)->create(
            ['peer_id' => $peer->id, 'ip' => '98.165.38.51', 'is_ipv6' => false, 'port' => 55555]
        );

        $this->assertSame(1, Peer::count());
        $this->assertSame(1, PeerIP::count());
        $this->assertSame(1, PeerVersion::count());
        $this->assertSame(0, Snatch::count());

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'completed',
                    'port'       => $port,
                    'downloaded' => 800,
                    'uploaded'   => 200,
                    'left'       => $torrent->getRawOriginal('size'),
                    'key'        => $key . 'foo',
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame(
            'd14:failure reason16:Invalid peer_id.e',
            $response->getContent()
        );
        $this->assertSame(1, Peer::count());
        $this->assertSame(1, PeerIP::count());
        $this->assertSame(1, PeerVersion::count());
        $this->assertSame(0, Snatch::count());

        $freshPeer = Peer::firstOrFail();
        $this->assertSame($peer->uploaded, $freshPeer->uploaded);
        $this->assertSame($peer->downloaded, $freshPeer->downloaded);
        $this->assertSame($peer->left, $freshPeer->left);
    }

    public function testClientIpIsRequired(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams()),
            [
                'REMOTE_ADDR'     => '',
            ]
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => 'Client IP is required.'];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testInvalidClientIpReturnsErrorResponse(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams()),
            [
                'REMOTE_ADDR'     => '95.44.22.888',
            ]
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => 'Invalid IP address given - "95.44.22.888"'];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUserAgentIsRequired(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams()),
            [
                'HTTP_USER_AGENT' => '',
            ]
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => 'User agent is required.'];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPasskeyIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'passkey' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'passkey'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPasskeyMustBe64CharsLong()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'passkey' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.size', ['var' => 'passkey'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPasskeyMustBeValid()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'passkey' => bin2hex(random_bytes(32)),
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.announce.invalid_passkey'), 'retry in' => 'never'];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testBannedUserCannotAnnounce()
    {
        $this->withoutExceptionHandling();

        $bannedUser = factory(User::class)->states('banned')->create();
        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'passkey' => $bannedUser->passkey,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.announce.banned_user'), 'retry in' => 'never'];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
        $freshBannedUser = $bannedUser->fresh();
        $this->assertSame($bannedUser->uploaded, $freshBannedUser->uploaded);
        $this->assertSame($bannedUser->downloaded, $freshBannedUser->downloaded);
    }

    public function testInfoHashIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'info_hash' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'info_hash'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testInfoHashMustBe20CharsLong()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'info_hash' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.size', ['var' => 'info_hash'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testInfoHashMustBeValid()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'info_hash' => hex2bin('ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d978'),
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.announce.invalid_info_hash')];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerIDIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'peer_id' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'peer_id'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerIDMustBe20CharsLong()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'peer_id' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.size', ['var' => 'peer_id'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerWithTheGivenPeerIDMustExistWhenTheEventIsCompleted()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'peer_id'    => hex2bin('2d7142333345302d64354e334474384672517777'),
                'event'      => 'completed',
                'downloaded' => 100,
                'uploaded'   => 200,
                'left'       => 0,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.announce.invalid_peer_id')];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerWithTheGivenPeerIDMustExistWhenTheEventIsStopped()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'peer_id' => hex2bin('2d7142333345302d64354e334474384672517777'),
                'event'   => 'stopped',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.announce.invalid_peer_id')];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testIPIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams()),
            [
                'REMOTE_ADDR'     => '',
            ]
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => 'Client IP is required.'];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'port' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'port'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortMustBeAnInteger()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'port' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.port', ['port' => 'xyz'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortMustBeHigherThan0()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'port' => 0,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.port', ['port' => 0])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortMustBeLowerThan65536()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'port' => 65536,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.port', ['port' => 65536])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUploadedIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'uploaded' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'uploaded'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUploadedMustBeAnInteger()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'uploaded' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.integer', ['var' => 'uploaded'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUploadedMustBeEqualToZeroOrGreater()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'uploaded' => -1,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.uploaded', ['uploaded' => -1])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testDownloadedIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'downloaded' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'downloaded'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testDownloadedMustBeAnInteger()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'downloaded' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.integer', ['var' => 'downloaded'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testDownloadedMustBeEqualToZeroOrGreater()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'downloaded' => -1,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.downloaded', ['downloaded' => -1])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testLeftIsRequired()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'left' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.required', ['var' => 'left'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testLeftMustBeAnInteger()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'left' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.integer', ['var' => 'left'])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testLeftMustBeEqualToZeroOrGreater()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            $this->app->make(UrlGenerator::class)->route('announce', $this->validParams([
                'left' => -1,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => trans('messages.validation.variable.left', ['left' => -1])];
        $decoder = new Bdecoder();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    /**
     * @param array $overrides
     */
    private function validParams($overrides = []): array
    {
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $user = factory(User::class)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        factory(TorrentInfoHash::class)->create(['info_hash' => $infoHash, 'torrent_id' => $torrent->id]);

        return array_merge([
            'info_hash'  => hex2bin($infoHash),
            'passkey'    => $user->passkey,
            'peer_id'    => hex2bin($peerId),
            'port'       => 65535,
            'downloaded' => 0,
            'uploaded'   => 0,
            'left'       => $torrent->getRawOriginal('size'),
            'event'      => 'started',
        ], $overrides);
    }
}
