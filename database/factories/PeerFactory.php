<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Peer;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PeerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Peer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'peer_id'    => bin2hex(random_bytes(20)),
            'torrent_id' => TorrentFactory::new(),
            'user_id'    => UserFactory::new(),
            'uploaded'   => $this->faker->numberBetween(0, 10000000),
            'downloaded' => $this->faker->numberBetween(0, 1000000),
            'left'       => $this->faker->numberBetween(0, \PHP_INT_MAX),
            'user_agent' => $this->faker->userAgent,
            'key'        => null,
        ];
    }

    public function seeder(): self
    {
        return $this->state([
            'left'       => 0,
            'downloaded' => 400,
            'uploaded'   => 200,
        ]);
    }

    public function leecher(): self
    {
        return $this->state([
            'left'       => 300,
            'downloaded' => 100,
            'uploaded'   => 20,
        ]);
    }

    public function versionOne(): self
    {
        return $this->has(PeerVersionFactory::new()->versionOne(), 'versions');
    }

    public function versionTwo(): self
    {
        return $this->has(PeerVersionFactory::new()->versionTwo(), 'versions');
    }

    public function hybrid(): self
    {
        return $this->has(PeerVersionFactory::new()->versionOne(), 'versions')
            ->has(PeerVersionFactory::new()->versionTwo(), 'versions');
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Peer $peer) {
            if (! $peer->versions()->exists()) {
                $peer->versions()->save(PeerVersionFactory::new()->make(['peer_id' => $peer->id]));
            }

            if (! $peer->ips()->exists()) {
                $peer->ips()->save(PeerIPFactory::new()->make(['peer_id' => $peer->id]));
            }
        });
    }
}
