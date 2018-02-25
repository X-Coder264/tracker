<?php

use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\Torrent;
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
