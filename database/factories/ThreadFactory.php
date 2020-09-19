<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrivateMessages\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subject' => $this->faker->unique()->firstName,
            'user_id' => UserFactory::new(),
        ];
    }
}
