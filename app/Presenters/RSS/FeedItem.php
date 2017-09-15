<?php

declare(strict_types=1);

namespace App\Presenters\RSS;

use Carbon\Carbon;

class FeedItem
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $link;

    /**
     * @var string
     */
    private $guid;

    /**
     * @var Carbon
     */
    private $pubDate;

    public function __construct(string $title, string $link, string $guid, Carbon $pubDate)
    {
        $this->title = $title;
        $this->link = $link;
        $this->guid = $guid;
        $this->pubDate = $pubDate;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function getPubDate(): Carbon
    {
        return $this->pubDate;
    }
}
