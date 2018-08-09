<?php

declare(strict_types=1);

use App\Http\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $user = new User();
        $user->name = 'PirateKing';
        $user->email = 'antonio.pauletich95@gmail.com';
        $user->password = '123456';
        $user->passkey = bin2hex(random_bytes(32));
        $user->locale_id = 1;
        $user->timezone = 'Europe/Zagreb';
        $user->torrents_per_page = 15;
        $user->save();
        $user->assignRole('Admin');

        $user = new User();
        $user->name = 'test';
        $user->email = 'test@gmail.com';
        $user->password = '123456';
        $user->passkey = bin2hex(random_bytes(32));
        $user->locale_id = 2;
        $user->timezone = 'UTC';
        $user->save();
        $user->assignRole('User');

        $user = new User();
        $user->name = 'test2';
        $user->email = 'test2@gmail.com';
        $user->password = '123456';
        $user->passkey = bin2hex(random_bytes(32));
        $user->locale_id = 1;
        $user->timezone = 'America/Los_Angeles';
        $user->save();
        $user->assignRole('User');
    }
}
