<?php

declare(strict_types=1);

use App\Http\Models\User;
use Illuminate\Support\Str;
use App\Http\Models\Torrent;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Torrent::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->firstName,
        'info_hash' => sha1(Str::random(200)),
        'size' => $faker->numberBetween(500, 500000),
        'uploader_id' => function () {
            return factory(User::class)->create()->id;
        },
        'description' => $faker->text(500),
        'seeders' => $faker->numberBetween(0, 100),
        'leechers' => $faker->numberBetween(0, 100),
    ];
});

$factory->state(Torrent::class, 'alive', [
    'seeders' => 1,
]);

$factory->state(Torrent::class, 'dead', [
    'seeders' => 0,
]);
