<?php

declare(strict_types=1);

use App\Models\User;
use Faker\Generator as Faker;
use App\Models\PrivateMessages\Thread;
use Illuminate\Database\Eloquent\Factory;
use App\Models\PrivateMessages\ThreadMessage;

/** @var Factory $factory */
$factory->define(ThreadMessage::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'thread_id' => function () {
            return factory(Thread::class)->create()->id;
        },
        'message' => $faker->text,
    ];
});
