<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Torrent;
use App\Models\TorrentCategory;
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
        $torrent->info_hash = bin2hex(random_bytes(20));
        $torrent->uploader_id = User::firstOrFail()->id;
        $torrent->category_id = TorrentCategory::firstOrFail()->id;
        $torrent->size = 515151514;
        $torrent->description = 'Description';
        $torrent->save();
    }
}
