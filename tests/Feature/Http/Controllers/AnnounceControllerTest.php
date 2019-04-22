<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Services\Bencoder;
use Generator;
use Tests\TestCase;

class AnnounceControllerTest extends TestCase
{
    /**
     * @dataProvider invalidPassKeyDataProvider
     */
    public function testStoreFailsIfPassKeyIsInvalid(?string $passKey): void
    {
        $bencoder = $this->app->make(Bencoder::class);

        $response = $this->get(
            route(
                'announce',
                [
                    'keypass' => $passKey,
                    'info_hash' => str_repeat('i', 20),
                    'peer_id' => str_repeat('p', 20),
                    'port' => 87,
                    'uploaded' => 5,
                    'downloaded' => 7,
                    'left' => 66,
                ]
            )
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain');
        $this->assertSame(
            'kita',
            $response->getContent()
        );
    }

    protected function validAnnounceParameters(array $change = []): array
    {

    }

    public function invalidPassKeyDataProvider(): Generator
    {
        yield 'null value' => [null];
        yield 'empty value' => [''];
        yield 'too short value' => [str_repeat('x', 63)];
        yield 'too big value' => [str_repeat('x', 65)];
    }

//    public function testStoreWithoutAnyValidationErrors(): void
//    {
//        $this->withoutExceptionHandling();
//
//        $response = $this->get(route('announce'));
//
//        $response->assertStatus(Response::HTTP_OK);
//        $response->assertViewIs('home.index');
//        $response->assertViewHasAll([
//            'usersCount' => 3,
//            'bannedUsersCount' => 2,
//            'peersCount' => 3,
//            'seedersCount' => 1,
//            'leechersCount' => 2,
//            'torrentsCount' => 4,
//            'deadTorrentsCount' => 3,
//            'totalTorrentSize' => $this->app->make(SizeFormatter::class)->getFormattedSize((int) Torrent::sum('size')),
//        ]);
//    }

    public function testStore()
    {
//        $announceService = $this->createMock(Manager::class);
//        $returnValue = 'test xyz 264';
//        $announceService->method('announce')->willReturn($returnValue);
//        $this->app->instance(Manager::class, $announceService);
//
//        $response = $this->get(route('announce'));
//
//        $response->assertStatus(Response::HTTP_OK);
//        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
//        $this->assertSame($returnValue, $response->getContent());
    }
}
