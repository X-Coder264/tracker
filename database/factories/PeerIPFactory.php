<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PeerIP;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PeerIPFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PeerIP::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'peer_id' => PeerFactory::new(),
            'ip'      => $this->faker->ipv4,
            'is_ipv6' => false,
            'port'    => $this->faker->numberBetween(50000, 60000),
        ];
    }
}
