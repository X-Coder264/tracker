<?php

declare(strict_types=1);

use App\Models\Peer;
use App\Models\Torrent;
use Illuminate\Database\Seeder;

class PeerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '1234';
        $peer->torrent_id = Torrent::firstOrFail()->id;
        $peer->userAgent = 'qBittorrent/3.3.16';
        $peer->save();

        $peer->IPs()->create(
            [
                'IP' => '5.93.165.5',
                'port' => 60755,
                'isIPv6' => false,
            ]
        );

        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '12345';
        $peer->torrent_id = Torrent::firstOrFail()->id;
        $peer->userAgent = 'qBittorrent/3.3.16';
        $peer->save();

        $peer->IPs()->createMany(
            [
                [
                    'IP' => '5.93.165.6',
                    'port' => 60756,
                    'isIPv6' => false,
                ],
                [
                    'IP' => '2001:db8:a0b:12f0::1',
                    'port' => 60755,
                    'isIPv6' => true,
                ],
            ]
        );
    }
}
