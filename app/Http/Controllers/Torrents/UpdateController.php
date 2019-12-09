<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torrents;

use App\Models\Torrent;
use App\Models\TorrentCategory;
use App\Services\IMDb\IMDBManager;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UpdateController
{
    private Gate $gate;
    private Factory $validatorFactory;
    private Translator $translator;
    private IMDBManager $IMDBManager;
    private ResponseFactory $responseFactory;

    public function __construct(
        Gate $gate,
        Factory $validatorFactory,
        Translator $translator,
        IMDBManager $IMDBManager,
        ResponseFactory $responseFactory
    ) {
        $this->gate = $gate;
        $this->validatorFactory = $validatorFactory;
        $this->translator = $translator;
        $this->IMDBManager = $IMDBManager;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, Torrent $torrent): RedirectResponse
    {
        try {
            $this->gate->authorize('update', $torrent);
        } catch (AuthorizationException $exception) {
            return $this->responseFactory->redirectToRoute('torrents.index')
                ->with('error', $this->translator->get('messages.torrent.not_allowed_to_edit'));
        }

        $validator = $this->validatorFactory->make(
            $request->all(),
            [
                'name' => 'required|string|min:5|max:255|unique:torrents',
                'description' => 'required|string|min:30',
                'category' => 'required|integer|exists:torrent_categories,id',
                'imdb_url' => 'nullable|url',
            ]
        );

        if ($validator->fails()) {
            return $this->responseFactory->redirectToRoute('torrents.edit', $torrent)
                ->withErrors($validator)
                ->withInput();
        }

        $torrent->name = $request->input('name');
        $torrent->description = $request->input('description');
        $torrent->category_id = $request->input('category');

        $category = TorrentCategory::findOrFail($request->input('category'));

        if (true === $request->filled('imdb_url') && true === $category->imdb) {
            try {
                $imdbId = $this->IMDBManager->getIMDBIdFromFullURL($request->input('imdb_url'));
                $torrent->imdb_id = $imdbId;
            } catch (Exception $exception) {
            }
        }

        $torrent->save();

        return $this->responseFactory->redirectToRoute('torrents.edit', $torrent)
            ->with('success', $this->translator->get('messages.torrent.successfully_updated'));
    }
}
