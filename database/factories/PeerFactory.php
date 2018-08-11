<?php

declare(strict_types=1);

use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Peer::class, function (Faker $faker) {
    return [
        'peer_id' => bin2hex(random_bytes(20)),
        'torrent_id' => function () {
            return factory(Torrent::class)->create()->id;
        },
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'uploaded' => $faker->numberBetween(0, 10000000),
        'downloaded' => $faker->numberBetween(0, 1000000),
        'seeder' => $faker->boolean(),
        'userAgent' => $faker->text(255),
    ];
});

$factory->state(Peer::class, 'seeder', [
    'seeder' => true,
    'downloaded' => 400,
    'uploaded' => 200,
]);

$factory->state(Peer::class, 'leecher', [
    'seeder' => false,
    'downloaded' => 100,
    'uploaded' => 20,
]);
