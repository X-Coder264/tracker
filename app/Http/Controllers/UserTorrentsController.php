<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Torrent;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

class UserTorrentsController
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(Guard $guard, ResponseFactory $responseFactory)
    {
        $this->guard = $guard;
        $this->responseFactory = $responseFactory;
    }

    public function show(): Response
    {
        /** @var User $user */
        $user = $this->guard->user();

        $torrents = Torrent::where('uploader_id', '=', $user->id)
            ->orderBy('id', 'desc')
            ->paginate($user->torrents_per_page);

        return $this->responseFactory->view('user-torrents.show', compact('torrents'));
    }
}
