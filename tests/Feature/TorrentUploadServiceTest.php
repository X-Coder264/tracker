<?php

namespace Tests\Feature;

use App\Http\Models\User;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;

class TorrentUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testTorrentUpload()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $decoderStub = $this->createMock(BdecodingService::class);
        App::instance(BdecodingService::class, $decoderStub);

        $decoderStub->method('decode')->willReturn(['test' => 'test']);

        $encoderStub = $this->createMock(BencodingService::class);
        App::instance(BencodingService::class, $encoderStub);

        $torrentValue = '123456';
        $encoderStub->method('encode')->willReturn($torrentValue);

        $response = $this->json('POST', route('torrent.store'), [
            'torrent' => File::create('file.torrent'),
            'name' => 'Test',
            'description' => 'Test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert the file was stored...
        Storage::disk('public')->assertExists('torrents/1.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/1.torrent'));
    }
}
