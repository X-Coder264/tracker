<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Services\SizeFormattingService;

class SizeFormattingServiceTest extends TestCase
{
    public function testFormatting()
    {
        $formatter = new SizeFormattingService();
        $this->assertSame('4.00 B', $formatter->getFormattedSize(4));
        $this->assertSame('1.00 KiB', $formatter->getFormattedSize(1024));
        $this->assertSame('4.00 KiB', $formatter->getFormattedSize(4096));
        $this->assertSame('4.00 MiB', $formatter->getFormattedSize(4194304));
        $this->assertSame('1.00 GiB', $formatter->getFormattedSize(1073741824));
        $this->assertSame('1.09 GiB', $formatter->getFormattedSize(1170378588));
        $this->assertSame('50.84 GiB', $formatter->getFormattedSize(54591057225));
    }
}
