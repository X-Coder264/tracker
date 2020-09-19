<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Torrent;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TorrentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Torrent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'        => $this->faker->unique()->firstName,
            'size'        => $this->faker->numberBetween(500, 500000),
            'imdb_id'     => null,
            'uploader_id' => UserFactory::new(),
            'category_id' => TorrentCategoryFactory::new(),
            'description' => $this->faker->text(500),
            'seeders'     => $this->faker->numberBetween(0, 100),
            'leechers'    => $this->faker->numberBetween(0, 100),
            'views_count' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function alive(): self
    {
        return $this->state([
            'seeders' => 1,
        ]);
    }

    public function dead(): self
    {
        return $this->state([
            'seeders' => 0,
        ]);
    }

    public function hasIMDB(): self
    {
        return $this->state([
            'imdb_id' => '0468569',
        ]);
    }

    public function versionOne(): self
    {
        return $this->has(TorrentInfoHashFactory::new()->versionOne(), 'infoHashes');
    }

    public function versionTwo(): self
    {
        return $this->has(TorrentInfoHashFactory::new()->versionTwo(), 'infoHashes');
    }

    public function hybrid(): self
    {
        return $this->has(TorrentInfoHashFactory::new()->versionOne(), 'infoHashes')
            ->has(TorrentInfoHashFactory::new()->versionTwo(), 'infoHashes');
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Torrent $torrent) {
            if (! $torrent->infoHashes()->exists()) {
                $torrent->infoHashes()->save(TorrentInfoHashFactory::new()->make(['torrent_id' => $torrent->id]));
            }
        });
    }
}
