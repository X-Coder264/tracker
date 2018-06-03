<?php

declare(strict_types=1);

use App\Http\Models\User;
use App\Http\Models\Torrent;
use Faker\Generator as Faker;
use App\Http\Models\TorrentComment;
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
