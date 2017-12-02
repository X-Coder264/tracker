<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use App\Http\Services\BdecodingService;
use App\Http\Services\BencodingService;
use Illuminate\Support\Facades\Storage;
use App\Http\Services\TorrentInfoService;
use App\Http\Services\SizeFormattingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    // TODO: add a test that shows that non private torrents get the private flag set to 1
    // TODO: add a test that shows that all torrents have an entropy value set to randomize the info_hash
    // TODO: add a test that shows that the info_hash must be unique in the DB (if it's not then a new entropy is calculated until it is)
    // TODO: add a test that shows that the correct announce URL is set

    public function testTorrentUpload()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        Storage::fake('public');

        $decoder = $this->createMock(BdecodingService::class);
        $this->app->instance(BdecodingService::class, $decoder);

        $decoder->method('decode')->willReturn(['test' => 'test']);

        $encoder = $this->createMock(BencodingService::class);
        $this->app->instance(BencodingService::class, $encoder);

        $infoService = $this->createMock(TorrentInfoService::class);
        $torrentSize = 5000;
        $infoService->method('getTorrentSize')->willReturn($torrentSize);
        $this->app->instance(TorrentInfoService::class, $infoService);

        $torrentValue = '123456';
        $encoder->method('encode')->willReturn($torrentValue);

        $torrentName = 'Test name';
        $torrentDescription = 'Test description';

        $response = $this->post(route('torrents.store'), [
            'torrent' => File::create('file.torrent'),
            'name' => $torrentName,
            'description' => $torrentDescription,
        ]);

        $torrent = Torrent::find(1);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('success');

        Storage::disk('public')->assertExists('torrents/1.torrent');
        $this->assertSame($torrentValue, Storage::disk('public')->get('torrents/1.torrent'));

        $formatter = new SizeFormattingService();

        $this->assertSame($torrentSize, (int) $torrent->getOriginal('size'));
        $this->assertSame($formatter->getFormattedSize($torrentSize), $torrent->size);
        $this->assertSame($torrentName, $torrent->name);
        $this->assertSame($torrentDescription, $torrent->description);
    }

    public function testGuestsCannotUploadTorrents()
    {
        $response = $this->post(route('torrents.store'), []);
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $this->assertSame(0, Torrent::count());
    }
}
