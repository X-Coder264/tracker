<?php

declare(strict_types=1);

namespace App\Services\RSS;

use App\Presenters\RSS\FeedItem;
use Illuminate\Contracts\View\Factory;

class Feed
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $items = [];

    public function __construct(string $title, string $url, string $description)
    {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
    }

    public function addItem(FeedItem $feedItem): self
    {
        $this->items[] = $feedItem;

        return $this;
    }

    public function render(Factory $viewFactory): string
    {
        return $viewFactory->make(
            'rss.rss20',
            ['title' => $this->title, 'url' => $this->url, 'description' => $this->description, 'items' => $this->items]
        )->render();
    }
}
