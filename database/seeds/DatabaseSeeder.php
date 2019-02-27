<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->call(LocaleSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(TorrentCategorySeeder::class);
        $this->call(TorrentSeeder::class);
        $this->call(PeerSeeder::class);
        $this->call(ThreadSeeder::class);
    }
}
