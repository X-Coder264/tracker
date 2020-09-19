<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrivateMessages\ThreadMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ThreadMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ThreadMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'   => UserFactory::new(),
            'thread_id' => ThreadFactory::new(),
            'message'   => $this->faker->text,
        ];
    }
}
