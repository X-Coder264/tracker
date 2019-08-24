<?php

declare(strict_types=1);

use Carbon\Carbon;
use App\Models\User;
use App\Models\Locale;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

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

/** @var Factory $factory */
$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->firstName,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'passkey' => bin2hex(random_bytes(32)),
        'remember_token' => Str::random(10),
        'locale_id' => function () {
            return factory(Locale::class)->create()->id;
        },
        'timezone' => 'Europe/Zagreb',
        'uploaded' => $faker->numberBetween(0, 10000000),
        'downloaded' => $faker->numberBetween(0, 1000000),
        'torrents_per_page' => $faker->numberBetween(5, 30),
        'banned' => false,
        'last_seen_at' => Carbon::now()->subMinutes($faker->numberBetween(1, 100)),
        'inviter_user_id' => null,
        'invites_amount' => 0,
        'is_two_factor_enabled' => false,
    ];
});

$factory->state(User::class, 'banned', [
    'banned' => true,
]);

$factory->state(User::class, 'has_available_invites', [
    'invites_amount' => 5,
]);

$factory->state(User::class, 'has_no_available_invites', [
    'invites_amount' => 0,
]);

$factory->state(User::class, '2fa_enabled', [
    'is_two_factor_enabled' => true,
]);

$factory->state(User::class, '2fa_disabled', [
    'is_two_factor_enabled' => false,
]);
