<?php

declare(strict_types=1);

use App\Models\Torrent;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Models\TorrentInfoHash;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(TorrentInfoHash::class, function (Faker $faker) {
    return [
        'info_hash' => sha1(Str::random(200)),
        'version' => 1,
        'torrent_id' => function () {
            return factory(Torrent::class)->create()->id;
        },
    ];
});

$factory->state(TorrentInfoHash::class, 'v2', [
    'info_hash' => substr(hash('sha256', Str::random(200)), 0, 40),
    'version' => 2,
]);
