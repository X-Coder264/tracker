<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TorrentCategory;
use Illuminate\Database\Seeder;

class TorrentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $torrentCategory = new TorrentCategory();
        $torrentCategory->name = 'Movies/x264';
        $torrentCategory->imdb = true;
        $torrentCategory->save();
    }
}
