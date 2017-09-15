<?php

use App\Http\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->name = 'PirateKing';
        $user->email = 'antonio.pauletich95@gmail.com';
        $user->password = bcrypt('123456', ['rounds' => 15]);
        $user->passkey = bin2hex(random_bytes(32));
        $user->locale_id = 1;
        $user->save();

        $user = new User();
        $user->name = 'test';
        $user->email = 'test@gmail.com';
        $user->password = bcrypt('123456', ['rounds' => 15]);
        $user->passkey = bin2hex(random_bytes(32));
        $user->locale_id = 2;
        $user->save();

        $user = new User();
        $user->name = 'test2';
        $user->email = 'test2@gmail.com';
        $user->password = bcrypt('123456', ['rounds' => 15]);
        $user->passkey = bin2hex(random_bytes(32));
        $user->locale_id = 1;
        $user->save();
    }
}
