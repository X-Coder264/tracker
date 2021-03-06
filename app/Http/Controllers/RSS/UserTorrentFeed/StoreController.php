<?php

declare(strict_types=1);

namespace App\Http\Controllers\RSS\UserTorrentFeed;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class StoreController
{
    private UrlGenerator $urlGenerator;
    private Guard $guard;
    private ResponseFactory $responseFactory;

    public function __construct(UrlGenerator $urlGenerator, Guard $guard, ResponseFactory $responseFactory)
    {
        $this->urlGenerator = $urlGenerator;
        $this->guard = $guard;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $this->guard->user();

        $categories = $request->input('categories');

        if (empty($categories)) {
            $url = $this->urlGenerator->route('torrents.rss', ['passkey' => $user->passkey]);
        } else {
            $categories = new Collection($categories);
            $categories = $categories->transform(function ($item) {
                return (int) $item;
            })->reject(function ($item) {
                return empty($item);
            })->implode(',');
            $url = $this->urlGenerator->route('torrents.rss', ['passkey' => $user->passkey, 'categories' => $categories]);
        }

        return $this->responseFactory->redirectToRoute('users.rss.show')->with('rssURL', $url);
    }
}
