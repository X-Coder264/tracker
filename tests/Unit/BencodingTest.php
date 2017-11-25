<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Services\BencodingService;

class BencodingTest extends TestCase
{
    /**
     * @var BencodingService
     */
    protected $encoder;

    protected function setUp()
    {
        parent::setUp();

        $this->encoder = new BencodingService();
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
    }

    public function testDictionaryWhichContainsAListEncoding()
    {
        $this->assertSame('d4:spaml1:a1:bee', $this->encoder->encode(['spam' => ['a', 'b']]));
    }

    public function testInvalidDataEncoding()
    {
        $this->assertSame(null, $this->encoder->encode(true));
    }
}
