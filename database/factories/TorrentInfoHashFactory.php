<?php

declare(strict_types=1);

use App\Models\Torrent;
use App\Models\TorrentInfoHash;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

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
