<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrivateMessages\ThreadParticipant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ThreadParticipantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ThreadParticipant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'      => UserFactory::new(),
            'thread_id'    => ThreadFactory::new(),
            'last_read_at' => null,
        ];
    }

    public function readTheThread(): self
    {
        return $this->state([
            'last_read_at' => CarbonImmutable::now(),
        ]);
    }
}
