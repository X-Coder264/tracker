<?php

declare(strict_types=1);

use App\Models\Peer;
use App\Models\PeerVersion;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(PeerVersion::class, function (Faker $faker) {
    return [
        'peerID' => function () {
            return factory(Peer::class)->create()->id;
        },
        'version' => 1,
    ];
});

$factory->state(PeerVersion::class, 'v2', [
    'version' => 2,
]);
