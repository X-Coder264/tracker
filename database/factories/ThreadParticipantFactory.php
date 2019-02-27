<?php

declare(strict_types=1);

use Carbon\Carbon;
use App\Models\User;
use Faker\Generator as Faker;
use App\Models\PrivateMessages\Thread;
use Illuminate\Database\Eloquent\Factory;
use App\Models\PrivateMessages\ThreadParticipant;

/** @var Factory $factory */
$factory->define(ThreadParticipant::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'thread_id' => function () {
            return factory(Thread::class)->create()->id;
        },
        'last_read_at' => null,
    ];
});

$factory->state(ThreadParticipant::class, 'readTheThread', [
    'last_read_at' => Carbon::now(),
]);
