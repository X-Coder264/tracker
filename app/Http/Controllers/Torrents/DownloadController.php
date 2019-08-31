<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\User;
use App\Models\Torrent;
use App\Services\Bdecoder;
use App\Services\Bencoder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DownloadController
{
    /**
     * @var Bencoder
     */
    private $encoder;

    /**
     * @var Bdecoder
     */
    private $decoder;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    public function __construct(
        Bencoder $encoder,
        Bdecoder $decoder,
        UrlGenerator $urlGenerator,
        Guard $guard,
        Translator $translator,
        FilesystemManager $filesystemManager
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->urlGenerator = $urlGenerator;
        $this->guard = $guard;
        $this->translator = $translator;
        $this->filesystemManager = $filesystemManager;
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
            $torrentFile = $this->filesystemManager->disk('torrents')->get("{$torrent->id}.torrent");
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException($this->translator->trans('messages.torrent-file-missing.error-message'));
        }

        $decodedTorrent = $this->decoder->decode($torrentFile);

        $passkey = $user->passkey;

        $decodedTorrent['announce'] = $this->urlGenerator->route('announce', ['passkey' => $passkey]);

        $response = new Response($this->encoder->encode($decodedTorrent));
        $response->headers->set('Content-Type', 'application/x-bittorrent');
        // TODO: add support for adding a prefix (or suffix) to the name of the file
        $fileName = str_replace(['/', '\\'], '', $torrent->name . '.torrent');
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            str_replace('%', '', Str::ascii($fileName))
        );

        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
