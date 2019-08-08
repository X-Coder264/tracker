<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use App\Models\Configuration;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use App\Enumerations\ConfigurationOptions;

/** @var Factory $factory */
$factory->define(Configuration::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->name,
        'value' => Str::random(10),
    ];
});

$factory->state(Configuration::class, 'invite_only_signup', [
    'name' => ConfigurationOptions::INVITE_ONLY_SIGNUP,
    'value' => true,
]);

$factory->state(Configuration::class, 'non_invite_only_signup', [
    'name' => ConfigurationOptions::INVITE_ONLY_SIGNUP,
    'value' => false,
]);
