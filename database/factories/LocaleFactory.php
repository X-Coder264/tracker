<?php

use App\Http\Models\Locale;
use Faker\Generator as Faker;

$factory->define(Locale::class, function (Faker $faker) {
    return [
        'locale' => $faker->unique()->name,
        'localeShort' => str_random(3),
    ];
});
