<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\PeerIP;
use App\Http\Models\Torrent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Facades\App\Http\Services\SizeFormattingService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerTest extends TestCase
{
    use RefreshDatabase;

    public function testUploadedAccessor()
    {
        factory(Peer::class)->create();
        $peer = Peer::findOrFail(1);
        $returnValue = '500 MB';
        SizeFormattingService::shouldReceive('getFormattedSize')->once()->with($peer->getOriginal('uploaded'))->willReturn($returnValue);
        $this->assertSame($returnValue, $peer->uploaded);
    }

    public function testDownloadedAccessor()
    {
        factory(Peer::class)->create();
        $peer = Peer::findOrFail(1);
        $returnValue = '500 MB';
        SizeFormattingService::shouldReceive('getFormattedSize')->once()->with($peer->getOriginal('downloaded'))->willReturn($returnValue);
        $this->assertSame($returnValue, $peer->downloaded);
    }

    public function testUserRelationship()
    {
        factory(Peer::class)->create(['user_id' => 1]);

        $user = User::findOrFail(1);
        $peer = Peer::findOrFail(1);
        $this->assertInstanceOf(BelongsTo::class, $peer->user());
        $this->assertInstanceOf(User::class, $peer->user);
        $this->assertSame($peer->user->id, $user->id);
        $this->assertSame($peer->user->name, $user->name);
        $this->assertSame($peer->user->slug, $user->slug);
    }

    public function testTorrentRelationship()
    {
        factory(Peer::class)->create(['user_id' => 1]);

        $torrent = Torrent::findOrFail(1);
        $peer = Peer::findOrFail(1);
        $this->assertInstanceOf(BelongsTo::class, $peer->torrent());
        $this->assertInstanceOf(Torrent::class, $peer->torrent);
        $this->assertSame($peer->torrent->id, $torrent->id);
        $this->assertSame($peer->torrent->name, $torrent->name);
        $this->assertSame($peer->torrent->slug, $torrent->slug);
    }

    public function testIPsRelationship()
    {
        factory(PeerIP::class)->create();

        $IP = PeerIP::findOrFail(1);
        $peer = Peer::findOrFail(1);
        $this->assertInstanceOf(HasMany::class, $peer->IPs());
        $this->assertInstanceOf(Collection::class, $peer->IPs);
        $this->assertSame($peer->IPs[0]->id, $IP->id);
        $this->assertSame($peer->IPs[0]->IP, $IP->IP);
        $this->assertSame($peer->IPs[0]->port, $IP->port);
        $this->assertSame($peer->IPs[0]->isIPv6, $IP->isIPv6);
        $this->assertSame($peer->IPs[0]->connectable, $IP->connectable);
    }
}
