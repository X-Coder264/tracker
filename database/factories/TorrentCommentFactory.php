<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TorrentComment;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TorrentCommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TorrentComment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'comment'    => $this->faker->text,
            'user_id'    => UserFactory::new(),
            'torrent_id' => TorrentFactory::new(),
        ];
    }
}
