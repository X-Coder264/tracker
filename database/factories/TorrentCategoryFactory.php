<?php

declare(strict_types=1);

use Faker\Generator as Faker;
use App\Models\TorrentCategory;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(TorrentCategory::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->firstName,
        'imdb' => $faker->boolean(),
    ];
});

$factory->state(TorrentCategory::class, 'canHaveIMDB', [
    'imdb' => true,
]);

$factory->state(TorrentCategory::class, 'cannotHaveIMDB', [
    'imdb' => false,
]);
