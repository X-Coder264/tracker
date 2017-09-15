<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Torrent;
use Faker\Generator as Faker;
use App\Models\TorrentComment;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(TorrentComment::class, function (Faker $faker) {
    return [
        'comment' => $faker->text,
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'torrent_id' => function () {
            return factory(Torrent::class)->create()->id;
        },
    ];
});
