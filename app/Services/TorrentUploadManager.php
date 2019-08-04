<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\Torrent;
use Illuminate\Http\Request;
use App\Models\TorrentCategory;
use App\Models\TorrentInfoHash;
use App\Services\IMDb\IMDBManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Filesystem\Filesystem;
use App\Services\IMDb\IMDBImagesManager;
use App\Exceptions\FileNotWritableException;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;

class TorrentUploadManager
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
     * @var Guard
     */
    private $guard;

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
     * @var IMDBManager
     */
    private $IMDBManager;

    /**
     * @var IMDBImagesManager
     */
    private $IMDBImagesManager;

    public function __construct(
        Bencoder $encoder,
        Bdecoder $decoder,
        TorrentInfoService $torrentInfoService,
        Guard $guard,
        Filesystem $filesystem,
        FilesystemManager $filesystemManager,
        UrlGenerator $urlGenerator,
        Translator $translator,
        IMDBManager $IMDBManager,
        IMDBImagesManager $IMDBImagesManager
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->torrentInfoService = $torrentInfoService;
        $this->guard = $guard;
        $this->filesystem = $filesystem;
        $this->filesystemManager = $filesystemManager;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->IMDBManager = $IMDBManager;
        $this->IMDBImagesManager = $IMDBImagesManager;
    }

    /**
     * @throws FileNotWritableException
     * @throws FileNotFoundException
     */
    public function upload(Request $request): Torrent
    {
        $torrentFile = $request->file('torrent');
        $torrentFilePath = $torrentFile->getRealPath();

        $torrentContent = $this->filesystem->get($torrentFilePath);

        try {
            $decodedTorrent = $this->decoder->decode($torrentContent);
        } catch (Exception $exception) {
            $this->throwInvalidTorrentFileException();
        }

        // the torrent must be private
        $decodedTorrent['info']['private'] = 1;

        $torrentSize = $this->torrentInfoService->getTorrentSize($decodedTorrent['info']);

        // TODO: add support for multiple announce URLs
        $decodedTorrent['announce'] = $this->urlGenerator->route('announce');

        do {
            $infoHashes = [];
            $torrentInfoHashModels = [];

            // add entropy to randomize info_hash in order to prevent peer leaking attacks
            // we are recalculating the entropy until we get an unique info_hash
            $decodedTorrent['info']['entropy'] = bin2hex(random_bytes(64));

            if (true === $this->torrentInfoService->isV1Torrent($decodedTorrent['info'])) {
                $v1TorrentInfoHash = $this->getV1TorrentInfoHash($decodedTorrent['info']);
                $infoHashes[] = $v1TorrentInfoHash;
                $torrentInfoHashModels[] = new TorrentInfoHash(['info_hash' => $v1TorrentInfoHash, 'version' => 1]);
            }

            if (true === $this->torrentInfoService->isV2Torrent($decodedTorrent['info'])) {
                $v2TorrentInfoHash = $this->getV2TruncatedTorrentInfoHash($decodedTorrent['info']);
                $infoHashes[] = $v2TorrentInfoHash;
                $torrentInfoHashModels[] = new TorrentInfoHash(['info_hash' => $v2TorrentInfoHash, 'version' => 2]);
            }

            if (empty($infoHashes)) {
                $this->throwInvalidTorrentFileException();
            }
        } while (true !== $this->areHashesUnique($infoHashes));

        $category = TorrentCategory::findOrFail($request->input('category'));

        $imdbId = null;
        if (true === $request->filled('imdb_url') && true === $category->imdb) {
            try {
                $imdbId = $this->IMDBManager->getIMDBIdFromFullURL($request->input('imdb_url'));
            } catch (Exception $exception) {
                $imdbId = null;
            }
        }

        $torrent = new Torrent();
        $torrent->name = $request->input('name');
        $torrent->size = $torrentSize;
        $torrent->description = $request->input('description');
        $torrent->uploader_id = $this->guard->id();
        $torrent->category_id = $category->id;
        $torrent->imdb_id = $imdbId;
        $torrent->save();
        $torrent->infoHashes()->saveMany($torrentInfoHashModels);

        $stored = $this->filesystemManager->disk('torrents')->put(
            "{$torrent->id}.torrent",
            $this->encoder->encode($decodedTorrent)
        );

        if (false !== $stored) {
            if (! empty($imdbId)) {
                $this->IMDBImagesManager->writePosterToDisk($imdbId);
            }

            return $torrent;
        }

        $torrent->delete();

        throw new FileNotWritableException($this->translator->trans('messages.file-not-writable-exception.error-message'));
    }

    protected function getV1TorrentInfoHash(array $torrentInfoDictionary): string
    {
        return sha1($this->encoder->encode($torrentInfoDictionary));
    }

    protected function getV2TruncatedTorrentInfoHash(array $torrentInfoDictionary): string
    {
        return substr(hash('sha256', $this->encoder->encode($torrentInfoDictionary)), 0, 40);
    }

    protected function areHashesUnique(array $infoHashes): bool
    {
        if (count(array_unique($infoHashes)) !== count($infoHashes)) {
            return false;
        }

        $count = 0;
        foreach ($infoHashes as $infoHash) {
            $count += TorrentInfoHash::where('info_hash', '=', $infoHash)->count();
        }

        if (0 === $count) {
            return true;
        }

        return false;
    }

    private function throwInvalidTorrentFileException(): void
    {
        throw ValidationException::withMessages(['torrent' => $this->translator->trans('messages.validation.torrent-upload-invalid-torrent-file')]);
    }
}
