<?php

declare(strict_types=1);

namespace App\Services;

class SizeFormatter
{
    /**
     * @var array
     */
    private $map = [
        0 => 'B',
        1 => 'KiB',
        2 => 'MiB',
        3 => 'GiB',
        4 => 'TiB',
    ];

    /**
     * @param int $sizeInBytes
     *
     * @return string
     */
    public function getFormattedSize(int $sizeInBytes): string
    {
        $count = 0;
        $size = $sizeInBytes;

        while ($size > 1023.99) {
            $size = $size / 1024;
            $count++;
        }

        $formattedString = number_format($size, 2, '.', '') . ' ' . $this->map[$count];

        return $formattedString;
    }
}