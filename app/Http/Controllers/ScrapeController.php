<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Bencoder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\ValidationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Services\Announce\Manager as AnnounceManager;

class ScrapeController
{
    /**
     * @var Bencoder
     */
    private $encoder;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var AnnounceManager
     */
    private $announceManager;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(
        Bencoder $encoder,
        ResponseFactory $responseFactory,
        Translator $translator,
        AnnounceManager $announceManager,
        ConnectionInterface $connection
    ) {
        $this->encoder = $encoder;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->announceManager = $announceManager;
        $this->connection = $connection;
    }

    public function show(Request $request): Response
    {
        $passkey = $request->input('passkey');

        if (empty($passkey) || 64 !== strlen($passkey)) {
            return $this->getErrorResponse($this->translator->trans('messages.announce.invalid_passkey'), true);
        }

        $user = $this->announceManager->getUser($passkey);

        if (null === $user) {
            return $this->getErrorResponse($this->translator->trans('messages.announce.invalid_passkey'), true);
        }

        if (true === (bool) $user->banned) {
            return $this->getErrorResponse($this->translator->trans('messages.announce.banned_user'), true);
        }

        $queryParameters = explode('&', $request->server->get('QUERY_STRING'));
        $infoHashes = [];

        foreach ($queryParameters as $parameter) {
            [$name, $value] = explode('=', $parameter);
            $decodedName = rawurldecode($name);
            if ('info_hash' === $decodedName) {
                $infoHash = rawurldecode(trim($value));
                if (20 === strlen($infoHash)) {
                    $infoHashes[] = $infoHash;
                }
            }
        }

        try {
            $data = $this->scrape($infoHashes);
        } catch (ValidationException $e) {
            return $this->getErrorResponse($e->validationMessages()[0]);
        }

        return $this->responseFactory->make(
            $data
        )->header('Content-Type', 'text/plain');
    }

    /**
     * @throws ValidationException
     */
    public function scrape(array $infoHashes): string
    {
        $response = [];

        foreach ($infoHashes as $infoHash) {
            $torrent = $this->connection
                ->table('torrents')
                ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
                ->where('info_hash', '=', bin2hex($infoHash))
                ->select(['torrents.id', 'seeders', 'leechers'])
                ->first();

            if (null === $torrent) {
                continue;
            }

            $snatchesCount = $this->connection
                ->table('snatches')
                ->where('torrent_id', '=', $torrent->id)
                ->where('left', '=', 0)
                ->count();

            $response['files'][$infoHash] = [
                'complete' => (int) $torrent->seeders,
                'incomplete' => (int) $torrent->leechers,
                'downloaded' => $snatchesCount,
            ];
        }

        if (empty($response['files'])) {
            throw ValidationException::single($this->translator->trans('messages.scrape.no_torrents'));
        }

        return $this->encoder->encode($response);
    }

    private function getErrorResponse(string $message, bool $neverRetry = false): Response
    {
        $data['failure reason'] = $message;

        if ($neverRetry) {
            $data['retry in'] = 'never';
        }

        return $this->responseFactory->make(
            $this->encoder->encode($data)
        )->header('Content-Type', 'text/plain');
    }
}
