<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\PeerVersion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PeerVersionTest extends TestCase
{
    use DatabaseTransactions;

    public function testPeerRelationship(): void
    {
        factory(PeerVersion::class)->create();

        $peerVersion = PeerVersion::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $peerVersion->peer());
        $this->assertInstanceOf(Peer::class, $peerVersion->peer);
        $this->assertSame($peer->peer_id, $peerVersion->peer->peer_id);
        $this->assertSame($peer->torrent_id, $peerVersion->peer->torrent_id);
        $this->assertSame($peer->user_id, $peerVersion->peer->user_id);
        $this->assertSame($peer->uploaded, $peerVersion->peer->uploaded);
        $this->assertSame($peer->downloaded, $peerVersion->peer->downloaded);
        $this->assertSame($peer->seeder, $peerVersion->peer->seeder);
        $this->assertSame($peer->userAgent, $peerVersion->peer->userAgent);
    }
}
