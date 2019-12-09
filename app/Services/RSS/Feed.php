<?php

declare(strict_types=1);

namespace App\Services\RSS;

use App\Presenters\RSS\FeedItem;
use Illuminate\Contracts\View\Factory;

class Feed
{
    private array $items = [];
    private Factory $viewFactory;

    public function __construct(Factory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    public function addItem(FeedItem $feedItem): self
    {
        $this->items[] = $feedItem;

        return $this;
    }

    public function render(string $title, string $url, string $description): string
    {
        return $this->viewFactory->make(
            'rss.rss20',
            ['title' => $title, 'url' => $url, 'description' => $description, 'items' => $this->items]
        )->render();
    }
}
