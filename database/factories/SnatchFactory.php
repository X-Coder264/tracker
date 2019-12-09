<?php

declare(strict_types=1);

use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Snatch::class, function (Faker $faker) {
    return [
        'torrent_id' => function () {
            return factory(Torrent::class)->create()->id;
        },
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'uploaded' => $faker->numberBetween(0, 10000000),
        'downloaded' => $faker->numberBetween(0, 1000000),
        'left' => $faker->numberBetween(1, 1000000),
        'seedTime' => 0,
        'leechTime' => 0,
        'timesAnnounced' => 1,
        'finished_at' => null,
        'userAgent' => $faker->text(255),
    ];
});

$factory->state(Snatch::class, 'snatched', [
    'finished_at' => Carbon::now()->subMinutes(10),
    'left' => 0,
]);
