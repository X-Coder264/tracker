<?php

declare(strict_types=1);

use App\Models\Peer;
use App\Models\PeerVersion;
use App\Models\Torrent;
use App\Models\User;
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
        'left' => $faker->numberBetween(0, \PHP_INT_MAX),
        'user_agent' => $faker->userAgent,
        'key' => null,
    ];
});

$factory->state(Peer::class, 'seeder', [
    'left' => 0,
    'downloaded' => 400,
    'uploaded' => 200,
]);

$factory->state(Peer::class, 'leecher', [
    'left' => 300,
    'downloaded' => 100,
    'uploaded' => 20,
]);

$factory->state(Peer::class, 'v1', [

]);

$factory->state(Peer::class, 'v2', [

]);

$factory->afterCreatingState(Peer::class, 'v1', function (Peer $peer, Faker $faker) {
    $peer->timestamps = false;
    $peer->versions()->save(new PeerVersion(['version' => 1]));
    $peer->timestamps = true;
});

$factory->afterCreatingState(Peer::class, 'v2', function (Peer $peer, Faker $faker) {
    $peer->timestamps = false;
    $peer->versions()->save(new PeerVersion(['version' => 2]));
    $peer->timestamps = true;
});
