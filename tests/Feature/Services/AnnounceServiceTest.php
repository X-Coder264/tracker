<?php

namespace Tests\Feature\Services;

use Carbon\Carbon;
use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\Snatch;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
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
        $this->assertSame($user->id, $peer->user_id);
        $this->assertSame($torrent->id, $peer->torrent_id);
        $this->assertSame(0, $peer->getOriginal('uploaded'));
        $this->assertSame(0, $peer->getOriginal('downloaded'));
        $this->assertFalse((bool) $peer->seeder);
        $this->assertSame($userAgent, $peer->userAgent);
        $this->assertInstanceOf(Carbon::class, $peer->created_at);
        $this->assertInstanceOf(Carbon::class, $peer->updated_at);
        $this->assertSame(1, Snatch::count());
        $snatch = Snatch::findOrFail(1);
        $this->assertSame($user->id, $snatch->user_id);
        $this->assertSame($torrent->id, $snatch->torrent_id);
        $this->assertSame(0, $snatch->getOriginal('uploaded'));
        $this->assertSame(0, $snatch->getOriginal('downloaded'));
        $this->assertSame($torrent->getOriginal('size'), $snatch->getOriginal('left'));
        $this->assertSame(0, $snatch->seedTime);
        $this->assertSame(0, $snatch->leechTime);
        $this->assertSame(1, $snatch->timesAnnounced);
        $this->assertNull($snatch->finishedAt);
        $this->assertSame($userAgent, $snatch->userAgent);
        $torrent = $torrent->fresh();
        $this->assertSame(1, $torrent->leechers);
        $this->assertSame(0, $torrent->seeders);
    }
}
