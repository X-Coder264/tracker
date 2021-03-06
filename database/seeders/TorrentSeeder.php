<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Torrent;
use App\Models\TorrentCategory;
use App\Models\TorrentInfoHash;
use App\Models\User;
use Illuminate\Database\Seeder;

class TorrentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $torrent = new Torrent();
        $torrent->name = 'test';
        $torrent->uploader_id = User::firstOrFail()->id;
        $torrent->category_id = TorrentCategory::firstOrFail()->id;
        $torrent->size = rand(1, 5151515140);
        $torrent->description = 'Description';
        $torrent->save();
        $torrent->infoHashes()->save(new TorrentInfoHash(['info_hash' => bin2hex(random_bytes(20)), 'version' => 1]));
    }
}
