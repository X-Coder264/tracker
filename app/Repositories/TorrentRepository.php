<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Torrent;
use Illuminate\Database\Eloquent\Collection;

class TorrentRepository
{
    public function getNewestAliveTorrentsInCategories(array $categoryIds = [], int $limit = 20): Collection
    {
        $queryBuilder = Torrent::with('infoHashes')
            ->where('seeders', '>', 0)
            ->orderByDesc('id')
            ->limit($limit);

        if (empty($categoryIds)) {
            return $queryBuilder->get();
        }

        return $queryBuilder
            ->whereIn('category_id', $categoryIds)
            ->get();
    }
}
