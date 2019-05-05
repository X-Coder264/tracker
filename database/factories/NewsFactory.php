<?php

declare(strict_types=1);

use App\Models\News;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(News::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'subject' => $faker->text(200),
        'text' => $faker->text(5000),
    ];
});
