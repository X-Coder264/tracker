<?php

declare(strict_types=1);

namespace App\Http\Controllers\Snatches;

use App\Enumerations\Cache;
use App\Models\Snatch;
use App\Models\Torrent;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ShowTorrentSnatchesController
{
    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Repository $cache, ResponseFactory $responseFactory)
    {
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, Torrent $torrent): Response
    {
        $page = (int) $request->input('page', 1);

        $snatches = $this->cache->remember(
            sprintf('torrent.%d.snatches.page.%d', $torrent->id, $page),
            Cache::TEN_MINUTES,
            function () use ($torrent): LengthAwarePaginator {
                return Snatch::with(['user'])->where('torrent_id', '=', $torrent->id)
                    ->orderBy('id', 'desc')
                    ->paginate();
            }
        );

        return $this->responseFactory->view('snatches.show', compact('torrent', 'snatches'));
    }
}
