<?php

namespace Tests\Unit\Http\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Http\Models\User;
use App\Http\Services\PasskeyService;
use PHPUnit\Framework\MockObject\MockObject;

class PasskeyServiceTest extends TestCase
{
    public function testGenerateUniquePasskey()
    {
        /* @var PasskeyService|MockObject $passkeyService */
        $passkeyService = $this->getMockBuilder(PasskeyService::class)
            ->setMethods(['generatePasskey', 'getUserWithTheSpecifiedPasskey'])
            ->getMock();
        $passkeyService->expects($this->exactly(2))
            ->method('generatePasskey')
            ->will($this->onConsecutiveCalls('xyz', 'xyq'));
        $passkeyService->expects($this->exactly(2))
            ->method('getUserWithTheSpecifiedPasskey')
            ->will($this->onConsecutiveCalls(new User(), null));

        $this->assertSame('xyq', $passkeyService->generateUniquePasskey());
    }

    public function testGeneratePasskey()
    {
        $passkeyService = new PasskeyService();
        $reflectionClass = new ReflectionClass(PasskeyService::class);
        $reflectionMethod = $reflectionClass->getMethod('generatePasskey');
        $reflectionMethod->setAccessible(true);
        $passkey = $reflectionMethod->invoke($passkeyService);
        $this->assertSame(64, strlen($passkey));
        $passkeyTwo = $reflectionMethod->invoke($passkeyService);
        $this->assertSame(64, strlen($passkeyTwo));
        $this->assertNotSame($passkey, $passkeyTwo);
    }
}
