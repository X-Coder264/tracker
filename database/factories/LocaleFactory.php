<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class LocaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Locale::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'locale'      => $this->faker->unique()->name,
            'localeShort' => Str::random(4),
        ];
    }
}
