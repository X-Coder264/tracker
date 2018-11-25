<?php

declare(strict_types=1);

namespace App\Http\Controllers\RSS;

use App\Models\User;
use App\Models\Torrent;
use App\Services\RSS\Feed;
use App\Enumerations\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\Factory;
use App\Repositories\TorrentRepository;
use Illuminate\Contracts\Cache\Repository;
use App\Services\RSS\TorrentFeedItemFactory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class TorrentFeedController
{
    /**
     * @var TorrentFeedItemFactory
     */
    private $torrentFeedItemFactory;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Factory
     */
    private $viewFactory;

    /**
     * @var TorrentRepository
     */
    private $torrentRepository;

    /**
     * @var Feed
     */
    private $feed;

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        TorrentFeedItemFactory $torrentFeedItemFactory,
        UrlGenerator $urlGenerator,
        Factory $viewFactory,
        TorrentRepository $torrentRepository,
        Repository $cache,
        Feed $feed,
        ResponseFactory $responseFactory
    ) {
        $this->torrentFeedItemFactory = $torrentFeedItemFactory;
        $this->urlGenerator = $urlGenerator;
        $this->viewFactory = $viewFactory;
        $this->torrentRepository = $torrentRepository;
        $this->cache = $cache;
        $this->feed = $feed;
        $this->responseFactory = $responseFactory;
    }

    public function show(Request $request, string $passkey): Response
    {
        $user = User::where('passkey', '=', $passkey)->first();

        if (null === $user) {
            throw new AuthenticationException();
        }

        $cacheKey = 'torrents.rss-feed';
        if ($request->filled('categories')) {
            $cacheKey .= '.' . $request->input('categories');
        }

        $cacheKey = md5($cacheKey);

        $feedContent = $this->cache->remember($cacheKey, Cache::TWO_MINUTES, function () use ($request, $user): string {
            $torrents = $this->getTorrents($request);

            $url = $this->urlGenerator->current();
            if ($request->filled('categories')) {
                $url .= sprintf('?categories=%s', rawurlencode($request->input('categories')));
            }

            $torrents->each(function (Torrent $torrent) use ($user) {
                $this->feed->addItem($this->torrentFeedItemFactory->make($torrent, $user->passkey));
            });

            return $this->feed->render('RSS feed', $url, 'Latest torrents');
        });

        return $this->responseFactory->make($feedContent)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    private function getTorrents(Request $request): EloquentCollection
    {
        if (! $request->filled('categories')) {
            return $this->torrentRepository->getNewestAliveTorrentsInCategories();
        } else {
            $categories = new Collection(explode(',', $request->input('categories')));
            $categories = $categories->transform(function ($item) {
                return (int) $item;
            })->reject(function ($item) {
                return empty($item);
            });

            return $this->torrentRepository->getNewestAliveTorrentsInCategories($categories->all());
        }
    }
}
