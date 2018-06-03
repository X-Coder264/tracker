<?php

declare(strict_types=1);

use Faker\Generator as Faker;
use App\Http\Models\Configuration;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Configuration::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->name,
        'value' => str_random(10),
    ];
});
