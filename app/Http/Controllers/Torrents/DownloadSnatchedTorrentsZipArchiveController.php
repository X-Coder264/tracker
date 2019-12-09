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

final class DownloadSnatchedTorrentsZipArchiveController
{
    private Guard $guard;
    private TorrentDownloadManipulator $torrentDownloadManipulator;
    private string $storagePath;
    private Translator $translator;
    private ResponseFactory $responseFactory;

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
            bin2hex(random_bytes(16)) . '_snatched_torrents.zip'
        );
        $zipArchive->open($zipFilePath, ZipArchive::CREATE);

        /** @var Builder $torrentQuery */
        $torrentQuery = Torrent::whereHas('snatches', function (Builder $query) {
            $query->where('user_id', '=', $this->guard->id());
        });

        $snatchedTorrentsFound = false;
        foreach ($torrentQuery->cursor() as $torrent) {
            $snatchedTorrentsFound = true;

            $zipArchive->addFromString(
                $this->torrentDownloadManipulator->getFallBackTorrentName($torrent->name),
                $this->torrentDownloadManipulator->getTorrentContent($torrent->id, $passkey)
            );
        }

        $zipArchive->close();

        if (! $snatchedTorrentsFound) {
            return $this->responseFactory->redirectToRoute('users.show', $this->guard->user())
                ->with('error', $this->translator->get('messages.no_snatches_for_zip_archive.message'));
        }

        $response = $this->responseFactory->download($zipFilePath, 'snatched_torrents.zip');
        $response->deleteFileAfterSend();

        return $response;
    }
}
