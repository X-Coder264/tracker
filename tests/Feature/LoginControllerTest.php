<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    public function testIndex()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }
}
