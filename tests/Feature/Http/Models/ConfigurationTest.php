<?php

namespace Tests\Feature\Http\Models;

use Tests\TestCase;
use App\Http\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function testGetConfigurationValueScope()
    {
        factory(Configuration::class)->create(['name' => 'test']);
        factory(Configuration::class)->create(['name' => 'test1']);
        $configuration = Configuration::getConfigurationValue('test');
        $this->assertSame('test', $configuration->name);
    }
}
