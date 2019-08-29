<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Snatch;
use App\Models\Torrent;
use App\Enumerations\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SnatchController
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

    public function show(Request $request, Torrent $torrent): Response
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
