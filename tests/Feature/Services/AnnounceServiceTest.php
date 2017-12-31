<?php

namespace Tests\Feature\Services;

use Carbon\Carbon;
use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\PeerIP;
use App\Http\Models\Snatch;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use App\Http\Services\BdecodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnnounceServiceTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertNull($snatch->finishedAt);
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
        $this->assertContains($responseContent, [$expectedResponseOne, $expectedResponseTwo]);
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
        $this->assertNull($snatch->finishedAt);
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
        $this->assertSame(0, Snatch::count());
        $torrent = $torrent->fresh();
        $this->assertSame(0, (int) $torrent->leechers);
        $this->assertSame(1, (int) $torrent->seeders);
    }
}
