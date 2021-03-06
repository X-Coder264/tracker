<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Peer;
use App\Models\PeerIP;
use App\Models\PeerVersion;
use App\Models\Torrent;
use App\Models\User;
use Database\Factories\PeerFactory;
use Database\Factories\PeerIPFactory;
use Database\Factories\PeerVersionFactory;
use Database\Factories\UserFactory;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PeerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUploadedAccessor()
    {
        PeerFactory::new()->create();
        $peer = Peer::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($peer->getRawOriginal('uploaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $peer->uploaded);
    }

    public function testDownloadedAccessor()
    {
        PeerFactory::new()->create();
        $peer = Peer::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($peer->getRawOriginal('downloaded'))->andReturn($returnValue);
        $this->assertSame($returnValue, $peer->downloaded);
    }

    public function testUserRelationship()
    {
        $user = UserFactory::new()->create();

        PeerFactory::new()->create(['user_id' => $user->id]);

        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $peer->user());
        $this->assertInstanceOf(User::class, $peer->user);
        $this->assertSame($peer->user->id, $user->id);
        $this->assertSame($peer->user->name, $user->name);
        $this->assertSame($peer->user->slug, $user->slug);
    }

    public function testTorrentRelationship()
    {
        $user = UserFactory::new()->create();

        PeerFactory::new()->create(['user_id' => $user->id]);

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
        PeerIPFactory::new()->create();

        $IP = PeerIP::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $peer->ips());
        $this->assertInstanceOf(Collection::class, $peer->ips);
        $this->assertSame($peer->ips[0]->id, $IP->id);
        $this->assertSame($peer->ips[0]->ip, $IP->ip);
        $this->assertSame($peer->ips[0]->port, $IP->port);
        $this->assertSame($peer->ips[0]->is_ipv6, $IP->is_ipv6);
    }

    public function testVersionsRelationship(): void
    {
        PeerVersionFactory::new()->create();

        $peerVersion = PeerVersion::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $peer->versions());
        $this->assertInstanceOf(Collection::class, $peer->versions);
        $this->assertSame($peerVersion->id, $peer->versions[0]->id);
        $this->assertSame($peerVersion->peer_id, $peer->versions[0]->peer_id);
    }
}
