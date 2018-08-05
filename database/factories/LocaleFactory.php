<?php

declare(strict_types=1);

use App\Http\Models\Locale;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Locale::class, function (Faker $faker) {
    return [
        'locale' => $faker->unique()->name,
        'localeShort' => Str::random(4),
    ];
});
