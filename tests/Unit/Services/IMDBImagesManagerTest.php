<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response;
use App\Services\IMDb\IMDBManager;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Support\Facades\Storage;
use App\Services\IMDb\IMDBImagesManager;
use PHPUnit\Framework\MockObject\MockObject;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class IMDBImagesManagerTest extends TestCase
{
    public function testWritePosterToDisk(): void
    {
        $imdbId = '0468569';
        $poster = 'some-data';
        $url = 'https://some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(false);
        Storage::shouldReceive('disk->put')->with($imdbId . '.jpg', $poster);

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $mock = new MockHandler([
            new Response(200, [], $poster),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

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
        $url = 'https://some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(false);
        Storage::shouldReceive('disk->put')->never();

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $container = [];
        $history = Middleware::history($container);

        $stack = HandlerStack::create();
        $stack->push($history);

        $client = new Client(['handler' => $stack]);

        $imdbImagesManager = new IMDBImagesManager(
            $imdbManager,
            $client,
            $this->app->make(FilesystemManager::class),
            $this->app->make(LoggerInterface::class)
        );
        $imdbImagesManager->writePosterToDisk($imdbId);

        foreach ($container as $transaction) {
            /** @var Request $request */
            $request = $transaction['request'];
            $this->assertSame('GET', $request->getMethod());
            $this->assertSame($url, sprintf('%s://%s', $request->getUri()->getScheme(), $request->getUri()->getHost()));
        }
    }

    public function testWritePosterToDiskWhenTheResponseIsNotSuccessful(): void
    {
        $imdbId = '0468569';
        $url = 'https://some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(false);
        Storage::shouldReceive('disk->put')->never();

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $mock = new MockHandler([
            new Response(500, []),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

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
        $url = 'https://some_image.jpg';

        Storage::fake('imdb-images');
        Storage::shouldReceive('disk->exists')->andReturn(true);
        Storage::shouldReceive('disk->put')->never();

        /** @var MockObject|IMDBManager $imdbManager */
        $imdbManager = $this->createPartialMock(IMDBManager::class, ['getPosterURLFromIMDBId']);
        $imdbManager->expects($this->once())->method('getPosterURLFromIMDBId')->with($imdbId)->willReturn($url);

        $container = [];
        $history = Middleware::history($container);

        $stack = HandlerStack::create();
        $stack->push($history);

        $client = new Client(['handler' => $stack]);

        $imdbImagesManager = new IMDBImagesManager(
            $imdbManager,
            $client,
            $this->app->make(FilesystemManager::class),
            $this->app->make(LoggerInterface::class)
        );
        $imdbImagesManager->writePosterToDisk($imdbId);

        $this->assertSame([], $container);
    }
}
