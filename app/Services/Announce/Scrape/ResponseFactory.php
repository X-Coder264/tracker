<?php

declare(strict_types=1);

namespace App\Services\Announce\Scrape;

use App\Exceptions\ValidationException;
use App\Services\Bencoder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory as IlluminateResponseFactory;
use Illuminate\Http\Response;

final class ResponseFactory
{
    /**
     * @var IlluminateResponseFactory
     */
    private $responseFactory;
    /**
     * @var Repository
     */
    private $config;
    /**
     * @var Bencoder
     */
    private $encoder;

    public function __construct(
        IlluminateResponseFactory $responseFactory,
        Repository $config,
        Bencoder $encoder
    ) {
        $this->responseFactory = $responseFactory;
        $this->config = $config;
        $this->encoder = $encoder;
    }

    public function success(array $data): Response
    {
        $response = [];
        foreach ($data as $hash => $value){
            $response['files'][$hash] = $value;
        }

        return $this->convertToResponse($response);
    }

    public function validationError(ValidationException $exception): Response
    {
        $response['failure reason'] = implode(' ', $exception->validationMessages());

        if (true === $exception->neverRetry()) {
            $response['retry in'] = 'never';
        }

        return $this->convertToResponse($response);
    }

    private function convertToResponse(array $data): Response
    {
        return $this->responseFactory
            ->make(
                $this->encoder->encode($data)
            )->header('Content-Type', 'text/plain');
    }
}
