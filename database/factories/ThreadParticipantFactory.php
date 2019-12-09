<?php

declare(strict_types=1);

use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadParticipant;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

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
