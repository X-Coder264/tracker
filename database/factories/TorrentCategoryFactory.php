<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TorrentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TorrentCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TorrentCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->firstName,
            'imdb' => $this->faker->boolean(),
        ];
    }

    public function hasIMDB(): self
    {
        return $this->state([
            'imdb' => true,
        ]);
    }

    public function doesNotHaveIMDB(): self
    {
        return $this->state([
            'imdb' => false,
        ]);
    }
}
