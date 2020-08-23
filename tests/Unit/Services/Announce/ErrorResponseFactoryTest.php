<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Announce;

use App\Services\Announce\ErrorResponseFactory;
use App\Services\Bencoder;
use PHPUnit\Framework\TestCase;

final class ErrorResponseFactoryTest extends TestCase
{
    /**
     * @param string|array $error
     *
     * @dataProvider dataProvider
     */
    public function testErrorResponseCreation($error, bool $neverRetry, string $expectedResponse): void
    {
        $this->assertSame($expectedResponse, (new ErrorResponseFactory(new Bencoder()))->create($error, $neverRetry));
    }

    public function dataProvider(): iterable
    {
        yield 'string error with never retry set to false' => [
            'test 123',
            false,
            'd14:failure reason8:test 123e',
        ];

        yield 'string error with never retry set to true' => [
            'test 123',
            true,
            'd14:failure reason8:test 1238:retry in5:nevere',
        ];

        yield 'array of errors with never retry set to false' => [
            ['foo 1', 'bar 2', 'baz'],
            false,
            'd14:failure reason15:foo 1 bar 2 baze',
        ];

        yield 'array of errors with never retry set to true' => [
            ['foo 1', 'bar 2', 'baz'],
            true,
            'd14:failure reason15:foo 1 bar 2 baz8:retry in5:nevere',
        ];
    }
}
