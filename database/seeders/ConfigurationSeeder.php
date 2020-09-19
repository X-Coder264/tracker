<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enumerations\ConfigurationOptions;
use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->getOptions() as $configOption) {
            $config = new Configuration();
            $config->name = $configOption[0];
            $config->value = $configOption[1];
            $config->save();
        }
    }

    private function getOptions(): array
    {
        return  [
            [ConfigurationOptions::INVITE_ONLY_SIGNUP, false],
        ];
    }
}
