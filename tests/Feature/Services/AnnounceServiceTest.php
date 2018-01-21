<?php

namespace Tests\Feature\Services;

use stdClass;
use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\PeerIP;
use App\Http\Models\Snatch;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Http\Services\BdecodingService;
use Tests\Traits\EnableForeignKeyConstraints;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnnounceServiceTest extends TestCase
{
    use RefreshDatabase, EnableForeignKeyConstraints;

    public function testStartLeechingWithNoOtherPeersPresentOnTheTorrent()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 0, 'leechers' => 0]);
        $user = factory(User::class)->create();

        $response = $this->get(
            route(
            'announce',
            [
                'info_hash'  => hex2bin($infoHash),
                'passkey'    => $user->passkey,
                'peer_id'    => hex2bin($peerId),
                'event'      => 'started',
                'ip'         => $IP,
                'port'       => $port,
                'downloaded' => 0,
                'uploaded'   => 0,
                'left'       => $torrent->getOriginal('size'),
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(1);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testStartLeechingWithOtherPeersPresentOnTheTorrent()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $peerIdOne = '2d7142333345302d64354e334474384672517777';
        $peerIdTwo = '2d7142333345302d64354e334474384672517778';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 1, 'leechers' => 1]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true, 'peer_id' => $peerIdOne]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => false, 'peer_id' => $peerIdTwo]);
        $peerTwoIP = factory(PeerIP::class)->create(['peerID' => $peerTwo->id, 'IP' => '98.165.38.52', 'port' => 55556]);

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ip'         => $IP,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note 1: because we use the "inRandomOrder" method in the getPeers method there can be two possible responses
        // Note 2: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponseOne = [
            'complete' => 1,
            'incomplete' => 1,
            'interval' => 2400,
            'min interval' => 60,
            'peers' => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port) . inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
            'peers6' => '',
        ];
        $expectedResponseTwo = [
            'complete' => 1,
            'incomplete' => 1,
            'interval' => 2400,
            'min interval' => 60,
            'peers' => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port) . inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6' => '',
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
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
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(2, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testStartSeedingWithNoOtherPeersPresentOnTheTorrent()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 0, 'leechers' => 0]);
        $user = factory(User::class)->create();

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertTrue((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(1);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testSeederDroppingOutOfTheSwarm()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IP = '98.165.38.50';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 1, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $peer = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'seeder'     => true,
                'peer_id'    => $peerId,
                'user_id'    => $user->id,
                'uploaded'   => 2000,
                'downloaded' => $torrent->getOriginal('size'),
                'created_at' => Carbon::now()->subMinutes(300),
                'updated_at' => Carbon::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peerID' => $peer->id, 'IP' => $IP, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'left'           => 0,
                'seedTime'       => 500,
                'leechTime'      => 1200,
                'timesAnnounced' => 2,
                'uploaded'       => 2000,
                'downloaded'     => $torrent->getOriginal('size'),
                'finished_at'    => Carbon::yesterday(),
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'stopped',
                    'ip'         => $IP,
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
        $this->assertSame(0, PeerIP::count());
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(3000, (int) $freshSnatch->getOriginal('uploaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $freshSnatch->getOriginal('downloaded'));
        $this->assertSame(0, (int) $freshSnatch->getOriginal('left'));
        $this->assertGreaterThanOrEqual(2900, (int) $freshSnatch->seedTime);
        $this->assertSame(1200, (int) $freshSnatch->leechTime);
        $this->assertSame(3, (int) $freshSnatch->timesAnnounced);
        $this->assertSame($snatch->finished_at->toDateTimeString(), $freshSnatch->finished_at->toDateTimeString());
        $this->assertSame($userAgent, $freshSnatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 1000, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 0,
                'leechers' => 1,
                'size'     => 3000,
            ]
        );
        $user = factory(User::class)->create();
        $peer = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'seeder'     => false,
                'peer_id'    => $peerId,
                'user_id'    => $user->id,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => Carbon::now()->subMinutes(300),
                'updated_at' => Carbon::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peerID' => $peer->id, 'IP' => $IP, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'left'           => 2000,
                'seedTime'       => 0,
                'leechTime'      => 1200,
                'timesAnnounced' => 2,
                'uploaded'       => 2000,
                'downloaded'     => 1000,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'stopped',
                    'ip'         => $IP,
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
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(3000, (int) $freshSnatch->getOriginal('uploaded'));
        $this->assertSame(2200, (int) $freshSnatch->getOriginal('downloaded'));
        $this->assertSame(800, (int) $freshSnatch->getOriginal('left'));
        $this->assertSame(0, (int) $freshSnatch->seedTime);
        $this->assertGreaterThanOrEqual(3600, (int) $freshSnatch->leechTime);
        $this->assertSame(3, (int) $freshSnatch->timesAnnounced);
        $this->assertNull($freshSnatch->finished_at);
        $this->assertSame($userAgent, $freshSnatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 1000, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded') + 1200, (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 1,
                'leechers' => 2,
                'size'     => 5000,
            ]
        );
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true, 'peer_id' => $peerIdOne]);
        factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => false, 'peer_id' => $peerIdTwo]);
        $peerTwoIP = factory(PeerIP::class)->create(['peerID' => $peerTwo->id, 'IP' => '98.165.38.52', 'port' => 55556]);

        $leecher = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'seeder'     => false,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => Carbon::now()->subMinutes(300),
                'updated_at' => Carbon::now()->subMinutes(40),
            ]
        );
        $leecherIP = factory(PeerIP::class)->create(['peerID' => $leecher->id, 'IP' => $IP, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'left'           => 4000,
                'seedTime'       => 0,
                'leechTime'      => 1000,
                'timesAnnounced' => 2,
                'uploaded'       => 2000,
                'downloaded'     => 1000,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'completed',
                    'ip'         => $IP,
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
            'peers'        => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
            'peers6'       => '',
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
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
        $this->assertSame(2000, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(5000, (int) $peer->getOriginal('downloaded'));
        $this->assertTrue((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(4);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(2000, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(5000, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertGreaterThanOrEqual(3400, (int) $snatch->leechTime);
        $this->assertSame(3, (int) $snatch->timesAnnounced);
        $this->assertNotNull($snatch->finished_at);
        $this->assertLessThanOrEqual(10, Carbon::now()->diffInSeconds($snatch->finished_at));
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded') + 4000, (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 1,
                'leechers' => 0,
                'size'     => 1000,
            ]
        );
        $user = factory(User::class)->create();
        $seeder = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'seeder'     => true,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => Carbon::now()->subMinutes(300),
                'updated_at' => Carbon::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peerID' => $seeder->id, 'IP' => $IP, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'left'           => 0,
                'seedTime'       => 3000,
                'leechTime'      => 1000,
                'timesAnnounced' => 5,
                'uploaded'       => 2000,
                'downloaded'     => 1000,
                'finished_at'    => Carbon::now()->subMinutes(200),
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(3000, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(1000, (int) $peer->getOriginal('downloaded'));
        $this->assertTrue((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(2);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(3000, (int) $freshSnatch->getOriginal('uploaded'));
        $this->assertSame(1000, (int) $freshSnatch->getOriginal('downloaded'));
        $this->assertSame(0, (int) $freshSnatch->getOriginal('left'));
        $this->assertGreaterThanOrEqual(3400, (int) $freshSnatch->seedTime);
        $this->assertSame($snatch->leechTime, (int) $freshSnatch->leechTime);
        $this->assertSame(6, (int) $freshSnatch->timesAnnounced);
        $this->assertNotNull($freshSnatch->finished_at);
        $this->assertSame($snatch->finished_at->toDateTimeString(), $freshSnatch->finished_at->toDateTimeString());
        $this->assertSame($userAgent, $freshSnatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 1000, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 0,
                'leechers' => 1,
                'size'     => 5000,
            ]
        );
        $user = factory(User::class)->create();
        $leecher = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'seeder'     => false,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => Carbon::now()->subMinutes(300),
                'updated_at' => Carbon::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peerID' => $leecher->id, 'IP' => $IP, 'port' => $port]);
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'left'           => 4000,
                'seedTime'       => 0,
                'leechTime'      => 1000,
                'timesAnnounced' => 5,
                'uploaded'       => 2000,
                'downloaded'     => 1000,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(2);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(2500, (int) $freshSnatch->getOriginal('uploaded'));
        $this->assertSame(1800, (int) $freshSnatch->getOriginal('downloaded'));
        $this->assertSame(3200, (int) $freshSnatch->getOriginal('left'));
        $this->assertSame(0, (int) $freshSnatch->seedTime);
        $this->assertGreaterThanOrEqual(3400, (int) $freshSnatch->leechTime);
        $this->assertSame(6, (int) $freshSnatch->timesAnnounced);
        $this->assertNull($freshSnatch->finished_at);
        $this->assertSame($userAgent, $freshSnatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 500, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded') + 800, (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 0,
                'leechers' => 1,
                'size'     => 5000,
            ]
        );
        $user = factory(User::class)->create();
        $leecher = factory(Peer::class)->create(
            [
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'seeder'     => false,
                'peer_id'    => $peerId,
                'uploaded'   => 2000,
                'downloaded' => 1000,
                'created_at' => Carbon::now()->subMinutes(300),
                'updated_at' => Carbon::now()->subMinutes(40),
            ]
        );
        factory(PeerIP::class)->create(['peerID' => $leecher->id, 'IP' => $IP, 'port' => $port]);

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(2);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 500, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded') + 800, (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 0,
                'leechers' => 0,
                'size'     => 5000,
            ]
        );
        $user = factory(User::class)->create();
        $snatch = factory(Snatch::class)->create(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'left'           => 0,
                'seedTime'       => 0,
                'leechTime'      => 1000,
                'timesAnnounced' => 5,
                'uploaded'       => 2000,
                'downloaded'     => 5000,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(1);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $freshSnatch = $snatch->fresh();
        $this->assertSame($user->id, (int) $freshSnatch->user_id);
        $this->assertSame($torrent->id, (int) $freshSnatch->torrent_id);
        $this->assertSame(4500, (int) $freshSnatch->getOriginal('uploaded'));
        $this->assertSame(6800, (int) $freshSnatch->getOriginal('downloaded'));
        $this->assertSame(3200, (int) $freshSnatch->getOriginal('left'));
        $this->assertSame(0, (int) $freshSnatch->seedTime);
        $this->assertSame(1000, (int) $freshSnatch->leechTime);
        $this->assertSame(6, (int) $freshSnatch->timesAnnounced);
        $this->assertNull($freshSnatch->finished_at);
        $this->assertSame($userAgent, $freshSnatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 2500, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded') + 1800, (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 0,
                'leechers' => 0,
                'size'     => 5000,
            ]
        );
        $user = factory(User::class)->create();

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(1800, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(1);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(0, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 2500, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded') + 1800, (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
                'infoHash' => $infoHash,
                'seeders'  => 0,
                'leechers' => 0,
                'size'     => 5000,
            ]
        );
        $user = factory(User::class)->create();

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'ip'         => $IP,
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
        $peer = Peer::findOrFail(1);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(2500, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertTrue((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, PeerIP::count());
        $peerIP = PeerIP::findOrFail(1);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded') + 2500, (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testPeerWithIPv4AndIPv6Address()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv4 = '98.165.38.50';
        $IPv6 = '2001::53aa:64c:0:7f83:bc43:dec9';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 2, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peerID' => $peerTwo->id,
                'IP'     => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port'   => 55556,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ipv4'       => $IPv4,
                    'ipv6'       => $IPv6,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IPv4,
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
            'peers'        => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(4, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IPv4, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $peerIP = PeerIP::findOrFail(4);
        $this->assertSame($IPv6, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertTrue((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testPeerWithIPv6AddressBEP7()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv6 = '2001::53aa:64c:0:7f83:bc43:dec9';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 2, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peerID' => $peerTwo->id,
                'IP'     => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port'   => 55556,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ipv6'       => $IPv6,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
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
            'peers'        => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IPv6, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertTrue((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testPeerWithIPv6Address()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv6 = '2001::53aa:64c:0:7f83:bc43:dec9';
        $port = 60000;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 2, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peerID' => $peerTwo->id,
                'IP'     => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port'   => 55556,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ip'         => $IPv6,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => '',
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
            'peers'        => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IPv6, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertTrue((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testPeerWithIPv6Endpoint()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv6 = '2001::53aa:64c:0:7f83:bc43:dec9';
        $IPv6Port = 60001;
        $port = 60000;
        $IPv6Endpoint = '[' . $IPv6 . ']:' . $IPv6Port;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 2, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peerID' => $peerTwo->id,
                'IP'     => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port'   => 55556,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ipv6'       => $IPv6Endpoint,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
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
            'peers'        => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IPv6, $peerIP->IP);
        $this->assertSame($IPv6Port, (int) $peerIP->port);
        $this->assertTrue((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testPeerWithIPv4Endpoint()
    {
        $this->withoutExceptionHandling();

        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $IPv4 = '98.165.38.50';
        $IPv4Port = 60001;
        $port = 60000;
        $IPv4Endpoint = $IPv4 . ':' . $IPv4Port;
        $userAgent = 'my test user agent';
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 2, 'leechers' => 0]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true]);
        $peerTwoIP = factory(PeerIP::class)->create(
            [
                'peerID' => $peerTwo->id,
                'IP'     => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port'   => 55556,
            ]
        );

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ipv4'       => $IPv4Endpoint,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
                ]
            ),
            [
                'REMOTE_ADDR'     => $IPv4,
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
            'peers'        => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6'       => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
        $responseContent = $decoder->decode($responseContent);
        if (! empty($responseContent['peers'])) {
            $responseContent['peers'] = bin2hex($responseContent['peers']);
        }
        if (! empty($responseContent['peers6'])) {
            $responseContent['peers6'] = bin2hex($responseContent['peers6']);
        }
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(3, Peer::count());
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IPv4, $peerIP->IP);
        $this->assertSame($IPv4Port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, (int) $torrent->leechers);
        $this->assertSame(2, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 1, 'leechers' => 1]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true, 'peer_id' => $peerIdOne]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ip'         => $IP,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
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
                    'ip'      => $peerOneIP->IP,
                    'peer id' => hex2bin($peerIdOne),
                    'port'    => $peerOneIP->port,
                ],
            ],
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
        $responseContent = $decoder->decode($responseContent);
        $this->assertSame($expectedResponse, $responseContent);
        $this->assertSame(2, Peer::count());
        $peer = Peer::findOrFail(2);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(2, PeerIP::count());
        $peerIP = PeerIP::findOrFail(2);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(2, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
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
        $torrent = factory(Torrent::class)->create(['infoHash' => $infoHash, 'seeders' => 1, 'leechers' => 1]);
        $user = factory(User::class)->create();
        $peerOne = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => true, 'peer_id' => $peerIdOne]);
        $peerOneIP = factory(PeerIP::class)->create(['peerID' => $peerOne->id, 'IP' => '98.165.38.51', 'port' => 55555]);
        $peerTwo = factory(Peer::class)->create(['torrent_id' => $torrent->id, 'seeder' => false, 'peer_id' => $peerIdTwo]);
        $peerTwoIP = factory(PeerIP::class)->create(['peerID' => $peerTwo->id, 'IP' => '98.165.38.52', 'port' => 55556]);

        $response = $this->get(
            route(
                'announce',
                [
                    'info_hash'  => hex2bin($infoHash),
                    'passkey'    => $user->passkey,
                    'peer_id'    => hex2bin($peerId),
                    'event'      => 'started',
                    'ip'         => $IP,
                    'port'       => $port,
                    'downloaded' => 0,
                    'uploaded'   => 0,
                    'left'       => $torrent->getOriginal('size'),
                    'numwant'    => 1,
                ]
            ),
            [
                'REMOTE_ADDR'     => $IP,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );

        // Note 1: because we use the "inRandomOrder" method in the getPeers method there can be two possible responses
        // Note 2: PHPUnit has some problems when asserting binary strings
        // so we use bin2hex on the expected and actual responses as a workaround
        $expectedResponseOne = [
            'complete'     => 1,
            'incomplete'   => 1,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6'       => '',
        ];
        $expectedResponseTwo = [
            'complete'     => 1,
            'incomplete'   => 1,
            'interval'     => 2400,
            'min interval' => 60,
            'peers'        => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
            'peers6'       => '',
        ];
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $responseContent = $response->getContent();
        $decoder = new BdecodingService();
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
        $peer = Peer::findOrFail(3);
        $this->assertSame($peerId, $peer->peer_id);
        $this->assertSame($user->id, (int) $peer->user_id);
        $this->assertSame($torrent->id, (int) $peer->torrent_id);
        $this->assertSame(0, (int) $peer->getOriginal('uploaded'));
        $this->assertSame(0, (int) $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(3, PeerIP::count());
        $peerIP = PeerIP::findOrFail(3);
        $this->assertSame($IP, $peerIP->IP);
        $this->assertSame($port, (int) $peerIP->port);
        $this->assertFalse((bool) $peerIP->isIPv6);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, (int) $snatch->user_id);
        $this->assertSame($torrent->id, (int) $snatch->torrent_id);
        $this->assertSame(0, (int) $snatch->getOriginal('uploaded'));
        $this->assertSame(0, (int) $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), (int) $snatch->getOriginal('left'));
        $this->assertSame(0, (int) $snatch->seedTime);
        $this->assertSame(0, (int) $snatch->leechTime);
        $this->assertSame(1, (int) $snatch->timesAnnounced);
        $this->assertNull($snatch->finished_at);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(2, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
        $freshUser = $user->fresh();
        $this->assertSame($user->getOriginal('uploaded'), (int) $freshUser->getOriginal('uploaded'));
        $this->assertSame($user->getOriginal('downloaded'), (int) $freshUser->getOriginal('downloaded'));
        $this->assertInstanceOf(stdClass::class, Cache::get('user.' . $freshUser->passkey));
        $this->assertSame((int) $freshUser->getOriginal('uploaded'), Cache::get('user.' . $freshUser->passkey)->uploaded);
        $this->assertSame((int) $freshUser->getOriginal('downloaded'), Cache::get('user.' . $freshUser->passkey)->downloaded);
    }

    public function testPasskeyIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'passkey' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'passkey'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPasskeyMustBe64CharsLong()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'passkey' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.size', ['var' => 'passkey'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPasskeyMustBeValid()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'passkey' => bin2hex(random_bytes(32)),
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.announce.invalid_passkey')];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testInfoHashIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'info_hash' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'info_hash'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testInfoHashMustBe20CharsLong()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'info_hash' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.size', ['var' => 'info_hash'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testInfoHashMustBeValid()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'info_hash' => hex2bin('ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d978'),
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.announce.invalid_info_hash')];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerIDIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'peer_id' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'peer_id'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerIDMustBe20CharsLong()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'peer_id' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.size', ['var' => 'peer_id'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerWithTheGivenPeerIDMustExistWhenTheEventIsCompleted()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            route('announce', $this->validParams([
                'peer_id'    => hex2bin('2d7142333345302d64354e334474384672517777'),
                'event'      => 'completed',
                'downloaded' => 100,
                'uploaded'   => 200,
                'left'       => 0,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.announce.invalid_peer_id')];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPeerWithTheGivenPeerIDMustExistWhenTheEventIsStopped()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(
            route('announce', $this->validParams([
                'peer_id' => hex2bin('2d7142333345302d64354e334474384672517777'),
                'event'   => 'stopped',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.announce.invalid_peer_id')];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testIPIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams(['ip' => ''])),
            [
                'REMOTE_ADDR'     => '',
            ]
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.announce.invalid_ip_or_port')];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'port' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'port'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortMustBeAnInteger()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'port' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.port', ['port' => 'xyz'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortMustBeHigherThan0()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'port' => 0,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.port', ['port' => 0])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testPortMustBeLowerThan65536()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'port' => 65536,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.port', ['port' => 65536])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUploadedIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'uploaded' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'uploaded'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUploadedMustBeAnInteger()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'uploaded' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.integer', ['var' => 'uploaded'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testUploadedMustBeEqualToZeroOrGreater()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'uploaded' => -1,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.uploaded', ['uploaded' => -1])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testDownloadedIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'downloaded' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'downloaded'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testDownloadedMustBeAnInteger()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'downloaded' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.integer', ['var' => 'downloaded'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testDownloadedMustBeEqualToZeroOrGreater()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'downloaded' => -1,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.downloaded', ['downloaded' => -1])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testLeftIsRequired()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'left' => '',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.required', ['var' => 'left'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testLeftMustBeAnInteger()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'left' => 'xyz',
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.integer', ['var' => 'left'])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    public function testLeftMustBeEqualToZeroOrGreater()
    {
        $response = $this->get(
            route('announce', $this->validParams([
                'left' => -1,
            ]))
        );
        $response->assertStatus(Response::HTTP_OK);
        $expectedResponse = ['failure reason' => __('messages.validation.variable.left', ['left' => -1])];
        $decoder = new BdecodingService();
        $actualResponse = $decoder->decode($response->getContent());
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame(0, Peer::count());
        $this->assertSame(0, Snatch::count());
    }

    /**
     * @param array $overrides
     *
     * @return array
     */
    private function validParams($overrides = []): array
    {
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $peerId = '2d7142333345302d64354e334474384672517776';
        $user = factory(User::class)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id, 'infoHash' => $infoHash]);

        return array_merge([
            'info_hash'  => hex2bin($infoHash),
            'passkey'    => $user->passkey,
            'peer_id'    => hex2bin($peerId),
            'ip'         => '98.165.38.50',
            'port'       => 65535,
            'downloaded' => 0,
            'uploaded'   => 0,
            'left'       => $torrent->getOriginal('size'),
            'event'      => 'started',
        ], $overrides);
    }
}
