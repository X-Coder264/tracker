<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestResponse;

final class JsonApiResponse
{
    /**
     * @var TestResponse
     */
    private $response;

    /**
     * @var array|null
     */
    private $jsonResponse;

    public function __construct(TestResponse $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);

        $this->response = $response;
        $this->jsonResponse = is_array($jsonResponse) ? $jsonResponse : null;
    }

    public function getResponse(): TestResponse
    {
        return $this->response;
    }

    public function getJsonResponse(): ?array
    {
        return $this->jsonResponse;
    }
}
