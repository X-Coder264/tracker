<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enumerations\ConfigurationOptions;
use App\Models\Configuration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ConfigurationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Configuration::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'  => $this->faker->unique()->name,
            'value' => Str::random(10),
        ];
    }

    public function inviteOnlySignup(): self
    {
        return $this->state([
            'name'  => ConfigurationOptions::INVITE_ONLY_SIGNUP,
            'value' => true,
        ]);
    }

    public function nonInviteOnlySignup(): self
    {
        return $this->state([
            'name'  => ConfigurationOptions::INVITE_ONLY_SIGNUP,
            'value' => false,
        ]);
    }
}
