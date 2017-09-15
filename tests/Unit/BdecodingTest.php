<?php

namespace Tests\Unit;

use App\Http\Services\BdecodingService;
use Tests\TestCase;
use Exception;

class BdecodingTest extends TestCase
{
    /**
     * @var BdecodingService
     */
    protected $decoder;

    protected function setUp()
    {
        parent::setUp();

        $this->decoder = new BdecodingService();
    }

    public function testStringDecoding()
    {
        $this->assertSame('spam', $this->decoder->decode('4:spam'));
    }

    public function testEmptyStringDecoding()
    {
        $this->assertSame('', $this->decoder->decode('0:'));
    }

    public function testPositiveIntegerDecoding()
    {
        $this->assertSame(3, $this->decoder->decode('i3e'));
    }

    public function testNegativeIntegerDecoding()
    {
        $this->assertSame(-3, $this->decoder->decode('i-3e'));
    }

    public function testIntegerDecodingWithZero()
    {
        $this->assertSame(0, $this->decoder->decode('i0e'));
    }

    public function testBigIntegerValuesDecoding()
    {
        if (4 === constant('PHP_INT_SIZE')) {
            $this->assertSame(4147483647.0, $this->decoder->decode('i4147483647e'));
            $this->assertSame(-4147483647.0, $this->decoder->decode('i-4147483647e'));
        } else {
            $this->assertSame(41474836470, $this->decoder->decode('i4147483647e'));
            $this->assertSame(-41474836470, $this->decoder->decode('i-4147483647e'));
        }
    }

    public function testListDecoding()
    {
        $this->assertSame(['spam', 'eggs'], $this->decoder->decode('l4:spam4:eggse'));
    }

    public function testEmptyListDecoding()
    {
        $this->assertSame([], $this->decoder->decode('le'));
    }

    public function testDictionaryDecoding()
    {
        $this->assertSame(['cow' => 'moo', 'spam' => 'eggs'], $this->decoder->decode('d3:cow3:moo4:spam4:eggse'));
    }

    public function testDictionaryWhichContainsAListDecoding()
    {
        $this->assertSame(['spam' => ['a', 'b']], $this->decoder->decode('d4:spaml1:a1:bee'));
    }

    public function testEmptyDictionaryDecoding()
    {
        $this->assertSame([], $this->decoder->decode('de'));
    }

    /**
     * Test that a string which is not valid triggers an exception
     */
    public function testNotValidStringThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('4:spa');
    }

    /**
     * Test that a zero-padded string length triggers an exception
     */
    public function testZeroPaddedStringLengthThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('04:spam');
    }

    /**
     * Test that a string without a colon triggers an exception
     */
    public function testStringWithoutColonThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('4spam');
    }

    /**
     * Test that an empty integer triggers an exception
     */
    public function testEmptyIntegerThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('ie');
    }

    /**
     * Test that a non-digit in an integer trigger an exception
     */
    public function testNonDigitCharInIntegerThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('iXe');
    }

    /**
     * Test that a zero-padded integer triggers an exception
     */
    public function testLeadingZeroInIntegerThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('i03e');
    }

    /**
     * Test that an unterminated integer triggers an exception
     */
    public function testUnterminatedIntegerThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('i3');
    }

    /**
     * Test that an unterminated lists triggers an exception
     */
    public function testUnterminatedListThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('l4:spam4:eggs');
    }

    /**
     * Test that an unterminated dictionary triggers an exception
     */
    public function testUnterminatedDictThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('d3:cow3:moo4:spam4:eggs');
    }

    /**
     * Test that a duplicate dictionary key triggers an exception
     */
    public function testDuplicateDictionaryKeyThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('d3:cow3:moo3:cow3:bare');
    }

    /**
     * Test that a non-string dictionary key triggers an exception
     */
    public function testNonStringDictKeyThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('di3e3:moo3:cow3:bare');
    }

    /**
     * Test that an unknown type triggers an exception
     */
    public function testUnknownTypeThrowsException()
    {
        $this->expectException(Exception::class);
        $this->decoder->decode('xyi3e3:moo3:cow3:bare');
    }
}
