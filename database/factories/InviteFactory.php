<?php

declare(strict_types=1);

use App\Models\Invite;
use App\Models\User;
use Carbon\CarbonImmutable;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

/** @var Factory $factory */
$factory->define(Invite::class, function (Faker $faker) {
    return [
        'code' => Str::random(255),
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'expires_at' => CarbonImmutable::now()->addHour(),
    ];
});

$factory->state(Invite::class, 'expired', [
    'expires_at' => CarbonImmutable::now()->subSecond(),
]);
