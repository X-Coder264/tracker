<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testEdit()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('users.edit', $user));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('users.edit');
        $response->assertViewHas(['user', 'locales']);
    }
}
