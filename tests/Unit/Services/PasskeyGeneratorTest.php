<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Http\Models\User;
use App\Services\PasskeyGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class PasskeyGeneratorTest extends TestCase
{
    public function testGenerateUniquePasskey()
    {
        /** @var PasskeyGenerator|MockObject $passkeyGenerator */
        $passkeyGenerator = $this->getMockBuilder(PasskeyGenerator::class)
            ->setMethods(['generatePasskey', 'getUserWithTheSpecifiedPasskey'])
            ->getMock();
        $passkeyGenerator->expects($this->exactly(2))
            ->method('generatePasskey')
            ->will($this->onConsecutiveCalls('xyz', 'xyq'));
        $passkeyGenerator->expects($this->exactly(2))
            ->method('getUserWithTheSpecifiedPasskey')
            ->will($this->onConsecutiveCalls(new User(), null));

        $this->assertSame('xyq', $passkeyGenerator->generateUniquePasskey());
    }

    public function testGeneratePasskey()
    {
        $passkeyGenerator = new PasskeyGenerator();
        $reflectionClass = new ReflectionClass(PasskeyGenerator::class);
        $reflectionMethod = $reflectionClass->getMethod('generatePasskey');
        $reflectionMethod->setAccessible(true);
        $passkey = $reflectionMethod->invoke($passkeyGenerator);
        $this->assertSame(64, strlen($passkey));
        $passkeyTwo = $reflectionMethod->invoke($passkeyGenerator);
        $this->assertSame(64, strlen($passkeyTwo));
        $this->assertNotSame($passkey, $passkeyTwo);
    }
}
