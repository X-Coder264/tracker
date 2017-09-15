<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Torrent;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Models\TorrentCategory;
use App\Models\TorrentInfoHash;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Torrent::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->firstName,
        'size' => $faker->numberBetween(500, 500000),
        'imdb_id' => null,
        'uploader_id' => function () {
            return factory(User::class)->create()->id;
        },
        'category_id' => function () {
            return factory(TorrentCategory::class)->create()->id;
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

$factory->state(Torrent::class, 'hasIMDB', [
    'imdb_id' => '0468569',
]);

$factory->state(Torrent::class, 'v1', [

]);

$factory->state(Torrent::class, 'v2', [

]);

$factory->state(Torrent::class, 'hybrid', [

]);

$factory->afterCreatingState(Torrent::class, 'v1', function (Torrent $torrent, Faker $faker) {
    $torrent->infoHashes()->save(new TorrentInfoHash(['info_hash' => sha1(Str::random(200)), 'version' => 1]));
});

$factory->afterCreatingState(Torrent::class, 'v2', function (Torrent $torrent, Faker $faker) {
    $torrent->infoHashes()->save(new TorrentInfoHash(['info_hash' => substr(hash('sha256', Str::random(200)), 0, 40), 'version' => 2]));
});

$factory->afterCreatingState(Torrent::class, 'hybrid', function (Torrent $torrent, Faker $faker) {
    $torrent->infoHashes()->saveMany([
        new TorrentInfoHash(['info_hash' => sha1(Str::random(200)), 'version' => 1]),
        new TorrentInfoHash(['info_hash' => substr(hash('sha256', Str::random(200)), 0, 40), 'version' => 2]),
    ]);
});
