<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\Torrent;
use App\Models\User;
use App\Services\TorrentDownloadManipulator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

final class DownloadSeedingTorrentsZipArchiveController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var TorrentDownloadManipulator
     */
    private $torrentDownloadManipulator;

    /**
     * @var string
     */
    private $storagePath;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        Guard $guard,
        TorrentDownloadManipulator $torrentDownloadManipulator,
        string $storagePath,
        Translator $translator,
        ResponseFactory $responseFactory
    ) {
        $this->guard = $guard;
        $this->torrentDownloadManipulator = $torrentDownloadManipulator;
        $this->storagePath = $storagePath;
        $this->translator = $translator;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(): Response
    {
        /** @var User $user */
        $user = $this->guard->user();
        $passkey = $user->passkey;

        $zipArchive = new ZipArchive();
        $zipFilePath = sprintf(
            '%s%s',
            $this->storagePath . DIRECTORY_SEPARATOR,
            bin2hex(random_bytes(16)) . '_seeding_torrents.zip'
        );
        $zipArchive->open($zipFilePath, ZipArchive::CREATE);

        /** @var Builder $torrentQuery */
        $torrentQuery = Torrent::whereHas('peers', function (Builder $query) {
            $query->where('user_id', '=', $this->guard->id())
                ->where('seeder', '=', true);
        });

        $seedingTorrentsFound = false;
        foreach ($torrentQuery->cursor() as $torrent) {
            $seedingTorrentsFound = true;

            $zipArchive->addFromString(
                $this->torrentDownloadManipulator->getFallBackTorrentName($torrent->name),
                $this->torrentDownloadManipulator->getTorrentContent($torrent->id, $passkey)
            );
        }

        $zipArchive->close();

        if (! $seedingTorrentsFound) {
            return $this->responseFactory->redirectToRoute('users.show', $this->guard->user())
                ->with('error', $this->translator->get('messages.no_seeding_torrents_for_zip_archive.message'));
        }

        $response = $this->responseFactory->download($zipFilePath, 'seeding_torrents.zip');
        $response->deleteFileAfterSend();

        return $response;
    }
}
