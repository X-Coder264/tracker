<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invite;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class InviteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invite::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code'       => Str::random(255),
            'user_id'    => UserFactory::new(),
            'expires_at' => CarbonImmutable::now()->addHour(),
        ];
    }

    public function expired(): self
    {
        return $this->state([
            'expires_at' => CarbonImmutable::now()->subSecond(),
        ]);
    }
}
