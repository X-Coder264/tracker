<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\Response;
use App\Http\Services\AnnounceService;

class AnnounceControllerTest extends TestCase
{
    public function testStore()
    {
        $announceService = $this->createMock(AnnounceService::class);
        $returnValue = 'test xyz 264';
        $announceService->method('announce')->willReturn($returnValue);
        $this->app->instance(AnnounceService::class, $announceService);

        $response = $this->get(route('announce'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame($returnValue, $response->getContent());
    }
}
