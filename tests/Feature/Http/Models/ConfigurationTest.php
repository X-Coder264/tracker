<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Models;

use Tests\TestCase;
use App\Http\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function testGetConfigurationValueScope(): void
    {
        factory(Configuration::class)->create(['name' => 'test']);
        factory(Configuration::class)->create(['name' => 'test1']);
        $configuration = Configuration::getConfigurationValue('test')->firstOrFail();
        $this->assertSame('test', $configuration->name);
    }
}
