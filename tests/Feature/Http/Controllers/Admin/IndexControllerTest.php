<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndex(): void
    {
        $user = UserFactory::new()->create();
        $this->actingAs($user);
        $response = $this->get($this->app->make(UrlGenerator::class)->route('admin.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('admin.index');
        $response->assertViewHasAll(
            [
                ['user' => $user],
                ['projectName' => $this->app->make(Repository::class)->get('app.name')],
                'enumerations',
            ]
        );
    }
}
