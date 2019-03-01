<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Exception;
use Tests\TestCase;
use App\Services\Bencoder;

class BencoderTest extends TestCase
{
    /**
     * @var Bencoder
     */
    protected $encoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = new Bencoder();
    }

    public function testStringEncoding()
    {
        $this->assertSame('4:spam', $this->encoder->encode('spam'));
    }

    public function testEmptyStringEncoding()
    {
        $this->assertSame('0:', $this->encoder->encode(''));
    }

    public function testPositiveIntegerEncoding()
    {
        $this->assertSame('i3e', $this->encoder->encode(3));
    }

    public function testNegativeIntegerEncoding()
    {
        $this->assertSame('i-3e', $this->encoder->encode(-3));
    }

    public function testIntegerEncodingWithZero()
    {
        $this->assertSame('i0e', $this->encoder->encode(0));
    }

    public function testListEncoding()
    {
        $this->assertSame('l4:spam4:eggse', $this->encoder->encode(['spam', 'eggs']));
    }

    public function testEmptyListEncoding()
    {
        $this->assertSame('le', $this->encoder->encode([]));
    }

    public function testDictionaryEncoding()
    {
        $this->assertSame('d3:cow3:moo4:spam4:eggse', $this->encoder->encode(['cow' => 'moo', 'spam' => 'eggs']));
        $this->assertSame('d3:bar4:spam3:fooi42ee', $this->encoder->encode(['bar' => 'spam', 'foo' => 42]));
        $this->assertSame('d3:bar4:spam3:fooi42ee', $this->encoder->encode(['foo' => 42, 'bar' => 'spam']));
    }

    public function testDictionaryWhichContainsAListEncoding()
    {
        $this->assertSame('d4:spaml1:a1:bee', $this->encoder->encode(['spam' => ['a', 'b']]));
    }

    public function testBooleanDataEncoding(): void
    {
        $this->expectException(Exception::class);
        $this->encoder->encode(true);
    }

    public function testFloatDataEncoding(): void
    {
        $this->expectException(Exception::class);
        $this->encoder->encode(1.0);
    }
}
