<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Torrents;

use App\Models\Peer;
use App\Models\Torrent;
use App\Models\User;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

final class DownloadSeedingTorrentsZipArchiveControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testZipArchiveDownload(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $this->actingAs($user);

        $torrent = factory(Torrent::class)->create(['name' => 'test foo čćšđ % X']);
        factory(Peer::class)->states('seeder')->create(['torrent_id' => $torrent->id, 'user_id' => $user->id]);
        factory(Peer::class)->states('leecher')->create(['user_id' => $user->id]);

        $torrentFileContent = file_get_contents(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'test.torrent'
        );

        $filesystem = $this->app->make(Factory::class);
        $filesystem->disk('torrents')->put($torrent->id . '.torrent', $torrentFileContent);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        try {
            $response = $this->get($urlGenerator->route('torrents.download-seeding-archive'));

            $response->assertOk();

            $response->assertHeader('Content-Type', 'application/zip');
            $response->assertHeader('Content-Disposition', 'attachment; filename=seeding_torrents.zip');

            $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

            /** @var BinaryFileResponse $binaryFileResponse */
            $binaryFileResponse = $response->baseResponse;

            $reflectionClass = new ReflectionClass(BinaryFileResponse::class);
            $reflectionProperty = $reflectionClass->getProperty('deleteFileAfterSend');
            $reflectionProperty->setAccessible(true);
            $this->assertTrue($reflectionProperty->getValue($binaryFileResponse));

            $file = $binaryFileResponse->getFile();

            $fileListInStorageFolder = glob($this->app->make('path.storage') . DIRECTORY_SEPARATOR . '*.{zip,torrent}', GLOB_BRACE);

            if (! empty($fileListInStorageFolder)) {
                foreach ($fileListInStorageFolder as $filePath) {
                    if ($filePath !== $file->getPathname()) {
                        unlink($filePath);
                    }
                }
            }

            $zipArchive = new ZipArchive();

            if ($zipArchive->open($file->getPathname())) {
                $zipArchive->extractTo($this->app->make('path.storage'));
                $zipArchive->close();
            } else {
                $this->fail('The ZIP archive could not have been opened.');
            }

            $unzippedTorrents = glob($this->app->make('path.storage') . DIRECTORY_SEPARATOR . '*.torrent');

            $this->assertCount(1, $unzippedTorrents);

            $this->assertSame('test foo ccsd  X.torrent', basename($unzippedTorrents[0]));

            $unzippedTorrentFileContent = file_get_contents($unzippedTorrents[0]);

            $decodedTorrentFileContent = $this->app->make(Bdecoder::class)->decode($torrentFileContent);
            $decodedTorrentFileContent['announce'] = $urlGenerator->route('announce', ['passkey' => $user->passkey]);
            $expectedTorrentFileContent = $this->app->make(Bencoder::class)->encode($decodedTorrentFileContent);
            $this->assertSame($expectedTorrentFileContent, $unzippedTorrentFileContent);
        } finally {
            if ($filesystem->disk('torrents')->exists($torrent->id . '.torrent')) {
                $filesystem->disk('torrents')->delete($torrent->id . '.torrent');
            }

            $fileListInStorageFolder = glob($this->app->make('path.storage') . DIRECTORY_SEPARATOR . '*.{zip,torrent}', GLOB_BRACE);

            foreach ($fileListInStorageFolder as $filePath) {
                unlink($filePath);
            }
        }
    }

    public function testTryingToDownloadArchiveWhenNoSeedingTorrentsExistForLoggedInUser(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $this->actingAs($user);

        factory(Peer::class)->states('seeder')->create();
        factory(Peer::class)->states('leecher')->create(['user_id' => $user->id]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        try {
            $response = $this->get($urlGenerator->route('torrents.download-seeding-archive'));

            $response->assertStatus(302);
            $response->assertRedirect($urlGenerator->route('users.show', $user));
            $response->assertSessionHas(
                'error',
                $this->app->make(Translator::class)->get('messages.no_seeding_torrents_for_zip_archive.message')
            );

            $fileListInStorageFolder = glob($this->app->make('path.storage') . DIRECTORY_SEPARATOR . '*.{zip,torrent}', GLOB_BRACE);
            $this->assertEmpty($fileListInStorageFolder);
        } finally {
            $fileListInStorageFolder = glob($this->app->make('path.storage') . DIRECTORY_SEPARATOR . '*.{zip,torrent}', GLOB_BRACE);

            foreach ($fileListInStorageFolder as $filePath) {
                unlink($filePath);
            }
        }
    }

    public function testGuestIsRedirectedToLoginPage(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('torrents.download-seeding-archive'));

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('login'));
    }
}