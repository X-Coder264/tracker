<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndexControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndex()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('admin.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('admin.index');
        $response->assertViewHasAll([['user' => $user], ['projectName' => config('app.name')], 'enumerations']);
    }
}
