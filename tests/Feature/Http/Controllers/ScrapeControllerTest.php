<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScrapeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testScrape(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $torrent = factory(Torrent::class)->create(['info_hash' => $infoHash, 'seeders' => 3, 'leechers' => 2]);
        factory(Snatch::class, 6)->states('snatched')->create(['torrent_id' => $torrent->id]);
        factory(Snatch::class, 2)->create(['torrent_id' => $torrent->id, 'left' => 1200]);

        $response = $this->get(route('scrape', ['info_hash'  => hex2bin($infoHash), 'passkey' => $user->passkey]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($response->getContent());

        $binaryInfoHash = hex2bin($infoHash);

        $this->assertCount(1, $responseContent['files']);
        $this->assertSame(3, $responseContent['files'][$binaryInfoHash]['complete']);
        $this->assertSame(2, $responseContent['files'][$binaryInfoHash]['incomplete']);
        $this->assertSame(6, $responseContent['files'][$binaryInfoHash]['downloaded']);
    }

    public function testScrapeWithMultipleInfoHashes(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $torrent = factory(Torrent::class)->create(['info_hash' => $infoHash, 'seeders' => 0, 'leechers' => 1]);
        $infoHashTwo = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d978';
        $torrentTwo = factory(Torrent::class)->create(['info_hash' => $infoHashTwo, 'seeders' => 1, 'leechers' => 4]);
        factory(Snatch::class, 1)->states('snatched')->create(['torrent_id' => $torrent->id]);
        factory(Snatch::class)->create(['torrent_id' => $torrent->id]);
        factory(Snatch::class, 2)->states('snatched')->create(['torrent_id' => $torrentTwo->id]);
        factory(Snatch::class)->create(['torrent_id' => $torrentTwo->id]);

        $binaryInfoHash = hex2bin($infoHash);
        $binaryInfoHashTwo = hex2bin($infoHashTwo);

        $response = $this->get(
            sprintf(
                '%s&info_hash=%s&info_hash=%s',
                route('scrape', ['passkey' => $user->passkey]),
                urlencode($binaryInfoHash),
                urlencode($binaryInfoHashTwo)
            )
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $decoder = new Bdecoder();
        $responseContent = $decoder->decode($response->getContent());

        $this->assertCount(2, $responseContent['files']);
        $this->assertSame(0, $responseContent['files'][$binaryInfoHash]['complete']);
        $this->assertSame(1, $responseContent['files'][$binaryInfoHash]['incomplete']);
        $this->assertSame(1, $responseContent['files'][$binaryInfoHash]['downloaded']);
        $this->assertSame(1, $responseContent['files'][$binaryInfoHashTwo]['complete']);
        $this->assertSame(4, $responseContent['files'][$binaryInfoHashTwo]['incomplete']);
        $this->assertSame(2, $responseContent['files'][$binaryInfoHashTwo]['downloaded']);
    }

    public function testScrapeWithNonExistingInfoHash(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $infoHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d977';
        $nonExistingHash = 'ccd285bd6d7fc749e9ed34d8b1e8a0f1b582d979';
        factory(Torrent::class)->create(['info_hash' => $infoHash, 'seeders' => 3, 'leechers' => 2]);

        $response = $this->get(route('scrape', ['info_hash'  => hex2bin($nonExistingHash), 'passkey' => $user->passkey]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $encoder = new Bencoder();

        $this->assertSame($encoder->encode(['failure reason' => trans('messages.scrape.no_torrents')]), $response->getContent());
    }

    public function testScrapeWithInvalidPasskey(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('scrape', ['passkey' => bin2hex(random_bytes(32))]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $encoder = new Bencoder();

        $this->assertSame(
            $encoder->encode(['failure reason' => trans('messages.announce.invalid_passkey'), 'retry in' => 'never']),
            $response->getContent()
        );
    }

    public function testScrapeWithInvalidLengthPasskey(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $response = $this->get(route('scrape', ['passkey' => $user->passkey . 'XYZ']));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $encoder = new Bencoder();

        $this->assertSame(
            $encoder->encode(['failure reason' => trans('messages.announce.invalid_passkey'), 'retry in' => 'never']),
            $response->getContent()
        );
    }

    public function testScrapeWithEmptyPasskey(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('scrape', ['passkey' => '']));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $encoder = new Bencoder();

        $this->assertSame(
            $encoder->encode(['failure reason' => trans('messages.announce.invalid_passkey'), 'retry in' => 'never']),
            $response->getContent()
        );
    }

    public function testScrapeWithBannedUserPasskey(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->states('banned')->create();

        $response = $this->get(route('scrape', ['passkey' => $user->passkey]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $encoder = new Bencoder();

        $this->assertSame(
            $encoder->encode(['failure reason' => trans('messages.announce.banned_user'), 'retry in' => 'never']),
            $response->getContent()
        );
    }
}
