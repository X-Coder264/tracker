<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use ReflectionClass;
use App\Services\PasskeyGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasskeyGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function testGetUserWithTheSpecifiedPasskey()
    {
        $user = factory(User::class)->create();

        $passkeyService = new PasskeyGenerator();
        $reflectionClass = new ReflectionClass(PasskeyGenerator::class);
        $reflectionMethod = $reflectionClass->getMethod('getUserWithTheSpecifiedPasskey');
        $reflectionMethod->setAccessible(true);
        $fetchedUser = $reflectionMethod->invokeArgs($passkeyService, [$user->passkey]);
        $this->assertInstanceOf(User::class, $fetchedUser);
        $this->assertSame($user->passkey, $fetchedUser->passkey);
        $this->assertNull($reflectionMethod->invokeArgs($passkeyService, ['non existing passkey']));
    }
}
