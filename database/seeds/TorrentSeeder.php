<?php

use App\Http\Models\Torrent;
use Illuminate\Database\Seeder;

class TorrentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $torrent = new Torrent();
        $torrent->name = 'test';
        $torrent->infoHash = bin2hex(random_bytes(20));
        $torrent->uploader_id = 1;
        $torrent->size = 515151514;
        $torrent->description = "Description";
        $torrent->save();
    }
}
