<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('home.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('home.index');
    }

    public function testGuestsCannotSeeTheHomePage()
    {
        $response = $this->get(route('home.index'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }
}