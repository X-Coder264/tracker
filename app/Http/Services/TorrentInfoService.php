<?php

declare(strict_types=1);

namespace App\Http\Services;

class TorrentInfoService
{
    /**
     * @var SizeFormattingService
     */
    private $sizeFormattingService;

    /**
     * @param SizeFormattingService $sizeFormattingService
     */
    public function __construct(SizeFormattingService $sizeFormattingService)
    {
        $this->sizeFormattingService = $sizeFormattingService;
    }

    /**
     * @param array $torrentInfoDict
     * @return int
     */
    public function getTorrentSize(array $torrentInfoDict)
    {
        $size = 0;
        if (isset($torrentInfoDict['files'])) {
            // multiple file mode
            foreach ($torrentInfoDict['files'] as $file) {
                $size += $file['length'];
            }
        } else {
            // single file mode
            $size = $torrentInfoDict['length'];
        }

        return $size;
    }

    /**
     * @param array $torrentInfoDict
     * @return array
     */
    public function getTorrentFileNamesAndSizes(array $torrentInfoDict)
    {
        $fileNamesAndSizes = [];

        if (isset($torrentInfoDict['files'])) {
            // multiple file mode
            foreach ($torrentInfoDict['files'] as $file) {
                $size = $this->sizeFormattingService->getFormattedSize($file['length']);
                $fileName = '';
                foreach ($file['path'] as $path) {
                    $fileName .= $path . '/';
                }

                $fileName = pathinfo($fileName, PATHINFO_BASENAME);
                $fileNamesAndSizes[] = [$fileName, $size];
            }
        } else {
            // single file mode
            $size = $this->sizeFormattingService->getFormattedSize($torrentInfoDict['length']);
            $fileName = $torrentInfoDict['name'];
            $fileNamesAndSizes[] = [$fileName, $size];
        }

        return $fileNamesAndSizes;
    }
}
