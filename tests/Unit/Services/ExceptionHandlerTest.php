<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Exception;
use Psr\Log\NullLogger;
use App\Exceptions\Handler;
use Psr\Log\LoggerInterface;
use Sentry\State\HubInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ExceptionHandlerTest extends TestCase
{
    public function testSentryExceptionReportingWhenSentryIsBoundInTheContainer(): void
    {
        $exception = new Exception('foobar');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('captureException')->with($exception);

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('bound')->with('sentry')->willReturn(true);

        $container->expects($this->exactly(2))->method('make')->willReturnCallback(function (string $argument) use ($hub) {
            if ('sentry' === $argument) {
                return $hub;
            }

            if (LoggerInterface::class === $argument) {
                return new NullLogger();
            }

            $this->fail(sprintf('This should not have happened. Container make function was called with "%s" argument.', $argument));
        });

        $exceptionHandler = new Handler($container);
        $exceptionHandler->report($exception);
    }

    public function testSentryExceptionReportingWhenSentryIsBoundInTheContainerButTheExceptionShouldNotBeReported(): void
    {
        $exception = new ModelNotFoundException();

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('captureException');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('bound')->with('sentry')->willReturn(true);
        $container->expects($this->never())->method('make');

        $exceptionHandler = new Handler($container);
        $exceptionHandler->report($exception);
    }

    public function testSentryExceptionReportingWhenSentryIsNotBoundInTheContainer(): void
    {
        $exception = new Exception('foobar');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('bound')->with('sentry')->willReturn(false);

        $container->expects($this->once())->method('make')->willReturnCallback(function (string $argument) {
            if ('sentry' === $argument) {
                $this->fail(sprintf('The Sentry service should not be attempted to be resolved from the container as it was not bound.'));
            }

            if (LoggerInterface::class === $argument) {
                return new NullLogger();
            }

            $this->fail(sprintf('This should not have happened. Container make function was called with "%s" argument.', $argument));
        });

        $exceptionHandler = new Handler($container);
        $exceptionHandler->report($exception);
    }
}
