<?php

declare(strict_types=1);

use App\Models\Peer;
use App\Models\PeerIP;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(PeerIP::class, function (Faker $faker) {
    return [
        'peerID' => function () {
            return factory(Peer::class)->create()->id;
        },
        'IP' => $faker->ipv4,
        'isIPv6' => false,
        'port' => $faker->numberBetween(50000, 60000),
    ];
});
