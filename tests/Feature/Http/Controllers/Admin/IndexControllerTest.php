<?php

namespace Tests\Feature\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Models\User;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('admin.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('admin.index');
        $response->assertViewHasAll([['user' => $user], ['projectName' => config('app.name')]]);
    }
}
