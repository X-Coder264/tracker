<?php

namespace App\Http\Services;

class SizeFormattingService
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
     * @param $sizeInBytes
     * @return string
     */
    public function getFormattedSize($sizeInBytes): string
    {
        $count = 0;
        $size = $sizeInBytes;

        while ($size > 1023.99) {
            $size = $size / 1024;
            $count++;
        }

        $formattedString = number_format($size, 2) . ' ' . $this->map[$count];

        return $formattedString;
    }
}
