<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Services\AnnounceManager;
use Illuminate\Http\Response;
use Tests\TestCase;

class AnnounceControllerTest extends TestCase
{
    public function testStore()
    {
        $announceService = $this->createMock(AnnounceManager::class);
        $returnValue = 'test xyz 264';
        $announceService->method('announce')->willReturn($returnValue);
        $this->app->instance(AnnounceManager::class, $announceService);

        $response = $this->get(route('announce'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame($returnValue, $response->getContent());
    }
}
