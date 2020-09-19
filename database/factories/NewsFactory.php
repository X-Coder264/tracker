<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

final class NewsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = News::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => UserFactory::new(),
            'subject' => $this->faker->text(200),
            'text'    => $this->faker->text(5000),
        ];
    }
}
