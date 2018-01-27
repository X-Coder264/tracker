<?php

namespace Tests\Feature\Http\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Http\Models\User;
use App\Http\Services\PasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasskeyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGetUserWithTheSpecifiedPasskey()
    {
        $user = factory(User::class)->create();

        $passkeyService = new PasskeyService();
        $reflectionClass = new ReflectionClass(PasskeyService::class);
        $reflectionMethod = $reflectionClass->getMethod('getUserWithTheSpecifiedPasskey');
        $reflectionMethod->setAccessible(true);
        $fetchedUser = $reflectionMethod->invokeArgs($passkeyService, [$user->passkey]);
        $this->assertInstanceOf(User::class, $fetchedUser);
        $this->assertSame($user->passkey, $fetchedUser->passkey);
        $this->assertNull($reflectionMethod->invokeArgs($passkeyService, ['non existing passkey']));
    }
}
