<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Configuration;
use Database\Factories\ConfigurationFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetConfigurationValueScope(): void
    {
        ConfigurationFactory::new()->create(['name' => 'test']);
        ConfigurationFactory::new()->create(['name' => 'test1']);
        $configuration = Configuration::getConfigurationValue('test')->firstOrFail();
        $this->assertSame('test', $configuration->name);
    }
}
