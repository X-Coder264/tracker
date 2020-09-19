<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PeerVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PeerVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PeerVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'peer_id' => PeerFactory::new(),
            'version' => 1,
        ];
    }

    public function versionOne(): self
    {
        return $this->state([
            'version' => 1,
        ]);
    }

    public function versionTwo(): self
    {
        return $this->state([
            'version' => 2,
        ]);
    }
}
