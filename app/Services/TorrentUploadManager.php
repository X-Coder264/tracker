<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\Torrent;
use Illuminate\Http\Request;
use App\Models\TorrentCategory;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use App\Exceptions\FileNotWritableException;
use Illuminate\Contracts\Routing\UrlGenerator;
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
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var IMDBManager
     */
    private $IMDBManager;

    /**
     * @var IMDBImagesManager
     */
    private $IMDBImagesManager;

    /**
     * @param Bencoder           $encoder
     * @param Bdecoder           $decoder
     * @param TorrentInfoService $torrentInfoService
     * @param AuthManager        $authManager
     * @param Filesystem         $filesystem
     * @param FilesystemManager  $filesystemManager
     * @param UrlGenerator       $urlGenerator
     * @param Translator         $translator
     * @param CacheManager       $cacheManager
     * @param IMDBManager        $IMDBManager
     * @param IMDBImagesManager  $IMDBImagesManager
     */
    public function __construct(
        Bencoder $encoder,
        Bdecoder $decoder,
        TorrentInfoService $torrentInfoService,
        AuthManager $authManager,
        Filesystem $filesystem,
        FilesystemManager $filesystemManager,
        UrlGenerator $urlGenerator,
        Translator $translator,
        CacheManager $cacheManager,
        IMDBManager $IMDBManager,
        IMDBImagesManager $IMDBImagesManager
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->torrentInfoService = $torrentInfoService;
        $this->authManager = $authManager;
        $this->filesystem = $filesystem;
        $this->filesystemManager = $filesystemManager;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->cacheManager = $cacheManager;
        $this->IMDBManager = $IMDBManager;
        $this->IMDBImagesManager = $IMDBImagesManager;
    }

    /**
     * @param Request $request
     *
     * @throws FileNotWritableException
     * @throws FileNotFoundException
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

        $category = TorrentCategory::where('id', '=', $request->input('category'))->firstOrFail();

        if (true === $request->filled('imdb_url') && true === $category->imdb) {
            try {
                $imdbId = $this->IMDBManager->getIMDBIdFromFullURL($request->input('imdb_url', ''));
            } catch (Exception $exception) {
                $imdbId = null;
            }
        }

        $torrent = new Torrent();
        $torrent->name = $request->input('name');
        $torrent->size = $torrentSize;
        $torrent->description = $request->input('description');
        $torrent->uploader_id = $this->authManager->guard()->id();
        $torrent->category_id = $category->id;
        $torrent->info_hash = $infoHash;
        $torrent->imdb_id = $imdbId ?? null;
        $torrent->save();

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
