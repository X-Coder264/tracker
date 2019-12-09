<?php

declare(strict_types=1);

use App\Models\Locale;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

/** @var Factory $factory */
$factory->define(Locale::class, function (Faker $faker) {
    return [
        'locale' => $faker->unique()->name,
        'localeShort' => Str::random(4),
    ];
});
