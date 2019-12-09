<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SecondsDurationFormatter;
use Tests\TestCase;

class SecondsDurationFormatterTest extends TestCase
{
    public function testFormatting(): void
    {
        /** @var SecondsDurationFormatter $formatter */
        $formatter = $this->app->make(SecondsDurationFormatter::class);

        $this->assertSame('-', $formatter->format(0));
        $this->assertSame('0:05', $formatter->format(5));
        $this->assertSame('0:15', $formatter->format(15));
        $this->assertSame('1:05', $formatter->format(65));
        $this->assertSame('8:20', $formatter->format(500));
        $this->assertSame('16:40', $formatter->format(1000));
        $this->assertSame('1:09:10', $formatter->format(4150));
        $this->assertSame('2:46:05', $formatter->format(9965));
        $this->assertSame('2:46:40', $formatter->format(10000));
        $this->assertSame('1d 03:46:40', $formatter->format(100000));
        $this->assertSame('1d 03:47:09', $formatter->format(100029));
        $this->assertSame('1d 04:00:10', $formatter->format(100810));
        $this->assertSame('11d 13:46:40', $formatter->format(1000000));
    }
}
