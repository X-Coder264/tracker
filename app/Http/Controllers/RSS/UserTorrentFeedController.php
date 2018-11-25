<?php

declare(strict_types=1);

namespace App\Http\Controllers\RSS;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TorrentCategory;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\ResponseFactory;

class UserTorrentFeedController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Guard $guard, UrlGenerator $urlGenerator, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->urlGenerator = $urlGenerator;
        $this->responseFactory = $responseFactory;
    }

    public function show(): Response
    {
        $categories = TorrentCategory::all();

        return $this->responseFactory->view('rss.show', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $categories = $request->input('categories');

        if (empty($categories)) {
            $url = $this->urlGenerator->route('torrents.rss', ['passkey' => $this->guard->user()->passkey]);
        } else {
            $categories = new Collection($categories);
            $categories = $categories->transform(function ($item) {
                return (int) $item;
            })->reject(function ($item) {
                return empty($item);
            })->implode(',');
            $url = $this->urlGenerator->route('torrents.rss', ['passkey' => $this->guard->user()->passkey, 'categories' => $categories]);
        }

        $redirect = new RedirectResponse($this->urlGenerator->route('users.rss.show'));
        $redirect->setSession($request->getSession());

        return $redirect->with('rssURL', $url);
    }
}
