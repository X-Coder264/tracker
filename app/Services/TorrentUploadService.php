<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use App\Exceptions\FileNotWritableException;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;

class TorrentUploadService
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
     * @var TorrentInfoService
     */
    private $torrentInfoService;

    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param Bencoder $encoder
     * @param Bdecoder $decoder
     * @param TorrentInfoService $torrentInfoService
     * @param AuthManager $authManager
     * @param Filesystem $filesystem
     * @param FilesystemManager $filesystemManager
     * @param UrlGenerator $urlGenerator
     * @param Translator $translator
     */
    public function __construct(
        Bencoder $encoder,
        Bdecoder $decoder,
        TorrentInfoService $torrentInfoService,
        AuthManager $authManager,
        Filesystem $filesystem,
        FilesystemManager $filesystemManager,
        UrlGenerator $urlGenerator,
        Translator $translator
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->torrentInfoService = $torrentInfoService;
        $this->authManager = $authManager;
        $this->filesystem = $filesystem;
        $this->filesystemManager = $filesystemManager;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     *
     * @throws FileNotWritableException
     *
     * @return Torrent
     */
    public function upload(Request $request): Torrent
    {
        $torrentFile = $request->file('torrent');
        $torrentFilePath = $torrentFile->getRealPath();

        $torrentContent = $this->filesystem->get($torrentFilePath);
        $decodedTorrent = $this->decoder->decode($torrentContent);

        // the torrent must be private
        $decodedTorrent['info']['private'] = 1;

        $torrentSize = $this->torrentInfoService->getTorrentSize($decodedTorrent['info']);

        // TODO: add support for multiple announce URLs
        $decodedTorrent['announce'] = $this->urlGenerator->route('announce');

        do {
            // add entropy to randomize info_hash in order to prevent peer leaking attacks
            // we are recalculating the entropy until we get an unique info_hash
            $decodedTorrent['info']['entropy'] = bin2hex(random_bytes(64));

            $infoHash = $this->getTorrentInfoHash($decodedTorrent['info']);

            $torrent = Torrent::where('info_hash', '=', $infoHash)->select('info_hash')->first();
        } while (null !== $torrent);

        $torrent = new Torrent();
        $torrent->name = $request->input('name');
        $torrent->size = $torrentSize;
        $torrent->description = $request->input('description');
        $torrent->uploader_id = $this->authManager->id();
        $torrent->info_hash = $infoHash;
        $torrent->save();

        $stored = $this->filesystemManager->disk('public')->put(
            "/torrents/{$torrent->id}.torrent",
            $this->encoder->encode($decodedTorrent)
        );

        if (false !== $stored) {
            return $torrent;
        }

        $torrent->delete();

        throw new FileNotWritableException($this->translator->trans('messages.file-not-writable-exception.error-message'));
    }

    /**
     * @param array $torrentInfoDictionary
     *
     * @return string
     */
    protected function getTorrentInfoHash(array $torrentInfoDictionary): string
    {
        return sha1($this->encoder->encode($torrentInfoDictionary));
    }
}
