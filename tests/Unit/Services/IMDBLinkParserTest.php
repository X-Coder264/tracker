<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use InvalidArgumentException;
use App\Services\IMDb\IMDBLinkParser;

class IMDBLinkParserTest extends TestCase
{
    /**
     * @var IMDBLinkParser
     */
    private $IMDBLinkParser;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->IMDBLinkParser = $this->app->make(IMDBLinkParser::class);
    }

    public function testIdParsing(): void
    {
        $this->assertSame('0111161', $this->IMDBLinkParser->getId('https://www.imdb.com/title/tt0111161/'));
        $this->assertSame('0111161', $this->IMDBLinkParser->getId('https://www.imdb.com/title/tt0111161/?ref_=nv_sr_1'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('https://www.imdb.com/title/tt0468569/'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('https://www.imdb.com/title/tt0468569'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('http://www.imdb.com/title/tt0468569/'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('http://www.imdb.com/title/tt0468569'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('www.imdb.com/title/tt0468569/'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('www.imdb.com/title/tt0468569'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('imdb.com/title/tt0468569/'));
        $this->assertSame('0468569', $this->IMDBLinkParser->getId('imdb.com/title/tt0468569'));
    }

    public function testExceptionIsThrownForAnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->IMDBLinkParser->getId('');
    }

    public function testExceptionIsThrownForAnInvalidURL(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->IMDBLinkParser->getId('https://www.imdb.com/title/0468569');
    }
}
