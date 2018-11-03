<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Generator;
use Tests\TestCase;
use App\Services\FileSizeCollectionFormatter;

class FileSizeCollectionFormatterTest extends TestCase
{
    public function dataProvider(): Generator
    {
        yield [
            ['testA' => 20, 'testB' => 44],
            ['testA' => '20.00 B', 'testB' => '44.00 B'],
        ];

        yield [
            ['testA' => 1023, 'testB' => 54591057225],
            ['testA' => '1023.00 B', 'testB' => '50.84 GiB'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testFormatting(array $testData, array $expected)
    {
        /** @var FileSizeCollectionFormatter $formatter */
        $formatter = $this->app->make(FileSizeCollectionFormatter::class);

        $index = 0;
        foreach ($formatter->format($testData) as $file => $formattedSize) {
            $this->assertSame(array_keys($expected)[$index], $file);
            $this->assertSame(array_values($expected)[$index], $formattedSize);
            $index++;
        }
    }
}
