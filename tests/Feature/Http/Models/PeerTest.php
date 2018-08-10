<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Models;

use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\PeerIP;
use App\Http\Models\Torrent;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertSame($peer->IPs[0]->connectable, $IP->connectable);
    }
}
