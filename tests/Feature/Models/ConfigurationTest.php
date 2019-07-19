<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetConfigurationValueScope(): void
    {
        factory(Configuration::class)->create(['name' => 'test']);
        factory(Configuration::class)->create(['name' => 'test1']);
        $configuration = Configuration::getConfigurationValue('test')->firstOrFail();
        $this->assertSame('test', $configuration->name);
    }
}
