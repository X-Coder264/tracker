<?php

declare(strict_types=1);

namespace App\Services\IMDb;

use App\Presenters\IMDb\Title;
use Imdb\Title as IMDbTitle;

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
