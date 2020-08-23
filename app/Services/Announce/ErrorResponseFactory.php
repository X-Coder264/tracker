<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Services\Bencoder;

final class ErrorResponseFactory
{
    private Bencoder $encoder;

    public function __construct(Bencoder $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param array|string $error
     */
    public function create($error, bool $neverRetry = false): string
    {
        $response['failure reason'] = '';

        if (is_array($error)) {
            $i = 0;
            $numberOfElements = count($error);
            foreach ($error as $message) {
                if ($numberOfElements - 1 === $i) {
                    $response['failure reason'] .= $message;
                } else {
                    $response['failure reason'] .= $message . ' ';
                }
                $i++;
            }
        } else {
            $response['failure reason'] = $error;
        }

        // BEP 31 -> http://www.bittorrent.org/beps/bep_0031.html
        if (true === $neverRetry) {
            $response['retry in'] = 'never';
        }

        return $this->encoder->encode($response);
    }
}
