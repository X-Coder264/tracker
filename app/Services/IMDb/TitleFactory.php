<?php

declare(strict_types=1);

namespace App\Services\IMDb;

use Imdb\Title as IMDbTitle;
use App\Presenters\IMDb\Title;

class TitleFactory
{
    public function make(IMDbTitle $imdbTitle): Title
    {
        $title = new Title();
        $title
            ->setId($imdbTitle->imdbid())
            ->setName($imdbTitle->title())
            ->setPlotOutline($imdbTitle->plotoutline(true))
            ->setRating($imdbTitle->rating())
            ->setGenres($imdbTitle->genres());

        return $title;
    }
}
