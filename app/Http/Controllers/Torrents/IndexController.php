<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Enumerations\Cache;
use App\Models\Torrent;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class IndexController
{
    private Guard $guard;
    private Repository $cache;
    private ResponseFactory $responseFactory;

    public function __construct(Guard $guard, Repository $cache, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request): Response
    {
        $page = (int) $request->input('page', 1);

        if (0 === $page) {
            $page = 1;
        }

        /** @var User $user */
        $user = $this->guard->user();
        $torrentPerPage = $user->torrents_per_page;

        $torrents = $this->cache->tags('torrents')->remember(
            'torrents.page.' . $page . '.perPage.' . $torrentPerPage,
            Cache::TEN_MINUTES,
            function () use ($torrentPerPage): LengthAwarePaginator {
                return Torrent::with(['uploader', 'category'])->where('seeders', '>', 0)
                    ->orderBy('id', 'desc')
                    ->paginate($torrentPerPage);
            }
        );

        return $this->responseFactory->view('torrents.index', compact('torrents'));
    }
}
