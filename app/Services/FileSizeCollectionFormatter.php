<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

class FileSizeCollectionFormatter
{
    /**
     * @var SizeFormatter
     */
    private $sizeFormatter;

    public function __construct(SizeFormatter $sizeFormatter)
    {
        $this->sizeFormatter = $sizeFormatter;
    }

    public function format(iterable $fileList): Generator
    {
        foreach ($fileList as $path => $size) {
            yield $path => $this->sizeFormatter->getFormattedSize($size);
        }
    }
}
