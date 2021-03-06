<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

class AdminApiTestCase extends TestCase
{
    protected function makeRequest(string $method, string $uri, array $data = [], array $headers = []): ?JsonApiResponse
    {
        $content = $data ? json_encode($data) : null;

        $defaultHeaders = ['Accept' => MediaTypeInterface::JSON_API_MEDIA_TYPE];

        if (null !== $content) {
            $defaultHeaders['CONTENT_LENGTH'] = mb_strlen($content, '8bit');
            $defaultHeaders['CONTENT_TYPE'] = MediaTypeInterface::JSON_API_MEDIA_TYPE;
        }

        $headers = array_merge($defaultHeaders, $headers);

        $server = $this->transformHeadersToServerVars($headers);

        $response = $this->call($method, $uri, [], [], [], $server, $content);

        $validResponseStatusCodes = [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT, Response::HTTP_UNPROCESSABLE_ENTITY];
        $this->assertContains($response->getStatusCode(), $validResponseStatusCodes);
        if ('DELETE' !== $method) {
            $response->assertHeader('CONTENT_TYPE', MediaTypeInterface::JSON_API_MEDIA_TYPE);

            $jsonApiResponse = new JsonApiResponse($response);

            $this->assertNotNull($jsonApiResponse->getJsonResponse());

            return $jsonApiResponse;
        }

        return null;
    }
}
