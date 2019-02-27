<?php

declare(strict_types=1);

use App\Models\User;
use Faker\Generator as Faker;
use App\Models\PrivateMessages\Thread;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(Thread::class, function (Faker $faker) {
    return [
        'subject' => $faker->unique()->firstName,
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
    ];
});
