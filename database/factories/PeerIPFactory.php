<?php

use App\Http\Models\Peer;
use App\Http\Models\PeerIP;
use Faker\Generator as Faker;

$factory->define(PeerIP::class, function (Faker $faker) {
    return [
        'peerID' => function () {
            return factory(Peer::class)->create()->id;
        },
        'IP' => $faker->ipv4,
        'isIPv6' => false,
        'connectable' => true,
    ];
});
