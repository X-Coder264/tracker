<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Peer;
use App\Models\PeerVersion;
use App\Models\Torrent;
use Illuminate\Database\Seeder;

class PeerSeeder extends Seeder
{
    public function run()
    {
        $torrent = Torrent::firstOrFail();
        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '1234';
        $peer->torrent_id = $torrent->id;
        $peer->user_agent = 'qBittorrent/3.3.16';
        $peer->left = 0;
        $peer->uploaded = rand(1, 500000);
        $peer->downloaded = rand(1, 500000);
        $peer->save();

        $peerVersion = new PeerVersion();
        $peerVersion->version = 1;
        $peer->versions()->save($peerVersion);

        $peer->ips()->create(
            [
                'ip'      => '5.93.165.5',
                'port'    => 60755,
                'is_ipv6' => false,
            ]
        );

        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '12345';
        $peer->torrent_id = $torrent->id;
        $peer->user_agent = 'qBittorrent/3.3.16';
        $peer->left = 500;
        $peer->uploaded = rand(1, 500000);
        $peer->downloaded = rand(1, 500000);
        $peer->save();

        $peerVersion = new PeerVersion();
        $peerVersion->version = 1;
        $peer->versions()->save($peerVersion);

        $peer->ips()->create(
            [
                'ip'      => '5.93.165.6',
                'port'    => 60756,
                'is_ipv6' => false,
            ]
        );

        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '123456';
        $peer->torrent_id = $torrent->id;
        $peer->user_agent = 'qBittorrent/3.3.16';
        $peer->left = 1200;
        $peer->uploaded = rand(1, 500000);
        $peer->downloaded = rand(1, 500000);
        $peer->save();

        $peerVersion = new PeerVersion();
        $peerVersion->version = 1;
        $peer->versions()->save($peerVersion);

        $peer->ips()->create(
            [
                'ip'      => '2001:db8:a0b:12f0::1',
                'port'    => 60756,
                'is_ipv6' => true,
            ]
        );
    }
}
