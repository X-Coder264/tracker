<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Snatch;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SnatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Snatch::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'torrent_id'      => TorrentFactory::new(),
            'user_id'         => UserFactory::new(),
            'uploaded'        => $this->faker->numberBetween(0, 10000000),
            'downloaded'      => $this->faker->numberBetween(0, 1000000),
            'left'            => $this->faker->numberBetween(1, 1000000),
            'seed_time'       => 0,
            'leech_time'      => 0,
            'times_announced' => 1,
            'finished_at'     => null,
            'user_agent'      => $this->faker->userAgent,
        ];
    }

    public function snatched(): self
    {
        return $this->state([
            'finished_at' => CarbonImmutable::now()->subMinutes(10),
            'left'        => 0,
        ]);
    }
}
