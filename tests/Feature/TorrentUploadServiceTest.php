<?php

namespace Tests\Feature;

use App\Http\Models\Torrent;
use App\Http\Models\User;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use App\Http\Services\SizeFormattingService;
use App\Http\Services\TorrentInfoService;
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
        $this->app->instance(BdecodingService::class, $decoderStub);

        $decoderStub->method('decode')->willReturn(['test' => 'test']);

        $encoderStub = $this->createMock(BencodingService::class);
        $this->app->instance(BencodingService::class, $encoderStub);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->method('getTorrentSize')->willReturn($torrentSize);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoderStub->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent' => File::create('file.torrent'),
            'name' => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::find(1);

        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        // Assert the file was stored...
        Storage::disk('public')->assertExists('torrents/1.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/1.torrent'));

        $formatter = new SizeFormattingService();

        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
    }
}
