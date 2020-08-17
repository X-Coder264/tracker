<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\IMDb\IMDBImagesManager;
use App\Services\IMDb\IMDBManager;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\TestCase;

final class IMDBImagesManagerTest extends TestCase
{
    public function testWritePosterToDisk(): void
    {
        $imdbId = '0468569';
        $poster = 'some-data';
        $url = 'https://www.foo.com/some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(false);
        Storage::shouldReceive('disk->put')->with($imdbId . '.jpg', $poster);

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $callback = function (string $method, string $requestedUrl) use ($poster, $url): MockResponse {
            if ('GET' === $method && $requestedUrl === $url) {
                return new MockResponse($poster);
            }

            $this->fail('This should not have happened');
        };

        $client = new MockHttpClient($callback);

        $imdbImagesManager = new IMDBImagesManager(
            $imdbManager,
            $client,
            $this->app->make(FilesystemManager::class),
            $this->app->make(LoggerInterface::class)
        );

        $imdbImagesManager->writePosterToDisk($imdbId);
    }

    public function testWritePosterToDiskWhenAnExceptionOccurs(): void
    {
        $imdbId = '0468569';
        $url = 'https://www.foo.com/some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(false);
        Storage::shouldReceive('disk->put')->never();

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $exception = new TransportException();
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())->method('request')->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'Imdb fetching error',
            [
                'exception' => $exception,
                'extra' => [
                    'imdbId' => $imdbId,
                    'url' => $url,
                ],
            ]
        );

        $imdbImagesManager = new IMDBImagesManager(
            $imdbManager,
            $client,
            $this->app->make(FilesystemManager::class),
            $logger
        );

        $imdbImagesManager->writePosterToDisk($imdbId);
    }

    public function testWritePosterToDiskWhenTheResponseIsNotSuccessful(): void
    {
        $imdbId = '0468569';
        $url = 'https://www.foo.com/some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(false);
        Storage::shouldReceive('disk->put')->never();

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $callback = function (): MockResponse {
            return new MockResponse('foo', ['http_code' => 500]);
        };

        $client = new MockHttpClient($callback);

        $imdbImagesManager = new IMDBImagesManager(
            $imdbManager,
            $client,
            $this->app->make(FilesystemManager::class),
            $this->app->make(LoggerInterface::class)
        );

        $imdbImagesManager->writePosterToDisk($imdbId);
    }

    public function testDoNotSendARequestIfTheFileIsAlreadyOnTheDisk(): void
    {
        $imdbId = '0468569';
        $url = 'https://www.foo.com/some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(true);
        Storage::shouldReceive('disk->put')->never();

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->never())->method('request');

        $imdbImagesManager = new IMDBImagesManager(
            $imdbManager,
            $client,
            $this->app->make(FilesystemManager::class),
            $this->app->make(LoggerInterface::class)
        );

        $imdbImagesManager->writePosterToDisk($imdbId);
    }
}
