<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'                  => $this->faker->unique()->firstName,
            'email'                 => $this->faker->unique()->safeEmail,
            'password'              => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'passkey'               => bin2hex(random_bytes(32)),
            'remember_token'        => Str::random(10),
            'locale_id'             => LocaleFactory::new(),
            'timezone'              => 'Europe/Zagreb',
            'uploaded'              => $this->faker->numberBetween(0, 10000000),
            'downloaded'            => $this->faker->numberBetween(0, 1000000),
            'torrents_per_page'     => $this->faker->numberBetween(5, 30),
            'banned'                => false,
            'last_seen_at'          => CarbonImmutable::now()->subMinutes($this->faker->numberBetween(1, 100)),
            'inviter_user_id'       => null,
            'invites_amount'        => 0,
            'is_two_factor_enabled' => false,
        ];
    }

    public function banned(): self
    {
        return $this->state([
            'banned' => true,
        ]);
    }

    public function hasAvailableInvites(): self
    {
        return $this->state([
            'invites_amount' => 5,
        ]);
    }

    public function hasNoAvailableInvites(): self
    {
        return $this->state([
            'invites_amount' => 0,
        ]);
    }

    public function twoFactorAuthEnabled(): self
    {
        return $this->state([
            'is_two_factor_enabled' => true,
        ]);
    }

    public function twoFactorAuthDisabled(): self
    {
        return $this->state([
            'is_two_factor_enabled' => false,
        ]);
    }
}
