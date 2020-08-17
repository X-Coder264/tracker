<?php

declare(strict_types=1);

namespace Tests\Feature\Providers;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\TestCase;

final class HttpClientTest extends TestCase
{
    public function testCurlHttpClientIsInstantiatedWhenRequestingASymfonyHttpClientImplementation(): void
    {
        $this->assertInstanceOf(CurlHttpClient::class, $this->app->make(HttpClientInterface::class));
    }
}
