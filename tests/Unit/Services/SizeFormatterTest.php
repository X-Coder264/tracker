<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SizeFormatter;
use Tests\TestCase;

class SizeFormatterTest extends TestCase
{
    public function testFormatting()
    {
        $formatter = new SizeFormatter();
        $this->assertSame('4.00 B', $formatter->getFormattedSize(4));
        $this->assertSame('1023.00 B', $formatter->getFormattedSize(1023));
        $this->assertSame('1.00 KiB', $formatter->getFormattedSize(1024));
        $this->assertSame('4.00 KiB', $formatter->getFormattedSize(4096));
        $this->assertSame('4.00 MiB', $formatter->getFormattedSize(4194304));
        $this->assertSame('1.00 GiB', $formatter->getFormattedSize(1073741824));
        $this->assertSame('1.09 GiB', $formatter->getFormattedSize(1170378588));
        $this->assertSame('50.84 GiB', $formatter->getFormattedSize(54591057225));
        $this->assertSame('50.84 TiB', $formatter->getFormattedSize(55899171157352));
        $this->assertSame('4.96 PiB', $formatter->getFormattedSize(5589917115735200));
    }
}
