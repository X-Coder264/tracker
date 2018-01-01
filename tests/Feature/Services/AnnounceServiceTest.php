<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\PeerIP;
use App\Http\Models\Snatch;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Http\Services\BdecodingService;
use Tests\Traits\EnableForeignKeyConstraints;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnnounceServiceTest extends TestCase
{
    use RefreshDatabase, EnableForeignKeyConstraints;

    public function testStartLeechingWithNoOtherPeersPresentOnTheTorrent()
    {
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
    }

    public function testStartLeechingWithOtherPeersPresentOnTheTorrent()
    {
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
    }

    public function testStartSeedingWithNoOtherPeersPresentOnTheTorrent()
    {
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
    }

    public function testSeederDroppingOutOfTheSwarm()
    {
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
    }

    public function testLeecherDroppingOutOfTheSwarm()
    {
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
                    'downloaded' => 2200,
                    'uploaded'   => 3000,
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
    }

    public function testLeecherCompletingTheTorrent()
    {
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
                    'downloaded' => 5000,
                    'uploaded'   => 2000,
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
            'complete'     => 0,
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
        $peerIP = $leecherIP->fresh();
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
    }

    public function testSeederContinuingToSeed()
    {
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
        $peerIP = PeerIP::findOrFail(1);
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
    }

    public function testLeecherContinuingToLeech()
    {
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
        $peerIP = PeerIP::findOrFail(1);
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
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
    }

    public function testPeerWithIPv4AndIPv6Address()
    {
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
                'IP' => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port' => 55556,
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
    }

    public function testPeerWithIPv6Address()
    {
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
                'IP' => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port' => 55556,
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
    }

    public function testPeerWithIPv6Endpoint()
    {
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
                'IP' => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port' => 55556,
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
    }

    public function testPeerWithIPv4Endpoint()
    {
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
                'IP' => '2001::53aa:64c:0:7f83:bc43:ded9',
                'isIPv6' => true,
                'port' => 55556,
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
    }

    public function testNumWantParameterIsRespected()
    {
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
            'complete' => 1,
            'incomplete' => 0,
            'interval' => 2400,
            'min interval' => 60,
            'peers' => bin2hex(inet_pton($peerOneIP->IP) . pack('n*', $peerOneIP->port)),
            'peers6' => '',
        ];
        $expectedResponseTwo = [
            'complete' => 0,
            'incomplete' => 1,
            'interval' => 2400,
            'min interval' => 60,
            'peers' => bin2hex(inet_pton($peerTwoIP->IP) . pack('n*', $peerTwoIP->port)),
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
    }
}
