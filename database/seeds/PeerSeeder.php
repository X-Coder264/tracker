<?php

use App\Http\Models\Peer;
use Illuminate\Database\Seeder;

class PeerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '1234';
        $peer->torrent_id = 1;
        $peer->left = 4432432;
        $peer->userAgent = "qBittorrent 3.3.16";
        $peer->save();

        $peer->IPs()->create(
            [
                'IP' => '5.93.165.5',
                'port' => 60755,
                'isIPv6' => false,
                'connectable' => false,
            ]
        );

        $peer = new Peer();
        $peer->user_id = rand(1, 3);
        $peer->peer_id = '12345';
        $peer->torrent_id = 1;
        $peer->left = 44324322;
        $peer->userAgent = "qBittorrent 3.3.16";
        $peer->save();

        $peer->IPs()->createMany(
            [
                [
                    'IP' => '5.93.165.6',
                    'port' => 60756,
                    'isIPv6' => false,
                    'connectable' => false,
                ],
                [
                    'IP' => '2001:db8:a0b:12f0::1',
                    'port' => 60755,
                    'isIPv6' => true,
                    'connectable' => false,
                ]
            ]
        );
    }
}
