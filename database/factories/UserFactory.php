<?php

use App\Http\Models\User;
use App\Http\Models\Locale;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    static $password;

    return [
        'name' => $faker->unique()->firstName,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = 'secret',
        'passkey' => bin2hex(random_bytes(32)),
        'remember_token' => str_random(10),
        'locale_id' => function () {
            return factory(Locale::class)->create()->id;
        },
        'timezone' => 'Europe/Zagreb',
        'slug' => $faker->unique()->text(255)
    ];
});
