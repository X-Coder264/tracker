<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\PeerIP;
use App\Models\Torrent;
use App\Models\PeerVersion;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PeerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUploadedAccessor()
    {
        factory(Peer::class)->create();
        $peer = Peer::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($peer->getOriginal('uploaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $peer->uploaded);
    }

    public function testDownloadedAccessor()
    {
        factory(Peer::class)->create();
        $peer = Peer::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($peer->getOriginal('downloaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $peer->downloaded);
    }

    public function testUserRelationship()
    {
        $user = factory(User::class)->create();

        factory(Peer::class)->create(['user_id' => $user->id]);

        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $peer->user());
        $this->assertInstanceOf(User::class, $peer->user);
        $this->assertSame($peer->user->id, $user->id);
        $this->assertSame($peer->user->name, $user->name);
        $this->assertSame($peer->user->slug, $user->slug);
    }

    public function testTorrentRelationship()
    {
        $user = factory(User::class)->create();

        factory(Peer::class)->create(['user_id' => $user->id]);

        $torrent = Torrent::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $peer->torrent());
        $this->assertInstanceOf(Torrent::class, $peer->torrent);
        $this->assertSame($peer->torrent->id, $torrent->id);
        $this->assertSame($peer->torrent->name, $torrent->name);
        $this->assertSame($peer->torrent->slug, $torrent->slug);
    }

    public function testIPsRelationship()
    {
        factory(PeerIP::class)->create();

        $IP = PeerIP::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $peer->IPs());
        $this->assertInstanceOf(Collection::class, $peer->IPs);
        $this->assertSame($peer->IPs[0]->id, $IP->id);
        $this->assertSame($peer->IPs[0]->IP, $IP->IP);
        $this->assertSame($peer->IPs[0]->port, $IP->port);
        $this->assertSame($peer->IPs[0]->isIPv6, $IP->isIPv6);
    }

    public function testVersionsRelationship(): void
    {
        factory(PeerVersion::class)->create();

        $peerVersion = PeerVersion::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $peer->versions());
        $this->assertInstanceOf(Collection::class, $peer->versions);
        $this->assertSame($peerVersion->id, $peer->versions[0]->id);
        $this->assertSame($peerVersion->peerID, $peer->versions[0]->peerID);
    }
}
