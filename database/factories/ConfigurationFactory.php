<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Http\Models\Configuration;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Configuration::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->name,
        'value' => Str::random(10),
    ];
});
