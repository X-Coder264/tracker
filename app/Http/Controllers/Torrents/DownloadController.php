<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\Torrent;
use App\Models\User;
use App\Services\TorrentDownloadManipulator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DownloadController
{
    private Guard $guard;
    private Translator $translator;
    private TorrentDownloadManipulator $torrentDownloadManipulator;

    public function __construct(
        Guard $guard,
        Translator $translator,
        TorrentDownloadManipulator $torrentDownloadManipulator
    ) {
        $this->guard = $guard;
        $this->translator = $translator;
        $this->torrentDownloadManipulator = $torrentDownloadManipulator;
    }

    public function __invoke(Request $request, Torrent $torrent): Response
    {
        $user = $this->guard->user();

        if (null === $user) {
            if (! $request->filled('passkey')) {
                throw new AuthenticationException();
            }

            $passkey = $request->input('passkey');

            $user = User::where('passkey', '=', $passkey)->first();

            if (null === $user) {
                throw new AuthenticationException();
            }
        }

        try {
            $torrentContent = $this->torrentDownloadManipulator->getTorrentContent($torrent->id, $user->passkey);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($this->translator->get('messages.torrent-file-missing.error-message'));
        }

        $response = new Response($torrentContent);
        $response->headers->set('Content-Type', 'application/x-bittorrent');

        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->torrentDownloadManipulator->getTorrentName($torrent->name),
            $this->torrentDownloadManipulator->getFallBackTorrentName($torrent->name)
        );

        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
