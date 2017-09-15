<?php

namespace Tests\Feature;

use App\Http\Models\User;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class TorrentUploadServiceTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $stub = $this->createMock(BdecodingService::class);
        $stub->method('decode')->willReturn(['test' => 'test']);
        //$stub->expects($this->any())->method('decode')->will($this->returnValue([]));
        App::instance(BdecodingService::class, $stub);

        $stub2 = $this->createMock(BencodingService::class);
        $stub2->method('encode')->willReturn('123456');

        $response = $this->json('POST', route('torrent.store'), [
            'torrent' => UploadedFile::fake()->create('file.torrent', 500),
            'description' => 'Test',
        ]);

        dd($response->getContent());

        // Assert the file was stored...
        Storage::disk('public')->assertExists('file.torrent');
    }
}
