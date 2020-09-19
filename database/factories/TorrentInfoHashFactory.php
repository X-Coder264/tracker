<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TorrentInfoHash;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class TorrentInfoHashFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TorrentInfoHash::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'info_hash'  => sha1(Str::random(200)),
            'version'    => 1,
            'torrent_id' => TorrentFactory::new(),
        ];
    }

    public function versionOne(): self
    {
        return $this->state([
            'version'   => 1,
            'info_hash' => sha1(Str::random(200)),
        ]);
    }

    public function versionTwo(): self
    {
        return $this->state([
            'version'   => 2,
            'info_hash' => substr(hash('sha256', Str::random(200)), 0, 40),
        ]);
    }
}
