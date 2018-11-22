<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Bencoder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AnnounceManager;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

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

    public function __construct(
        Bencoder $encoder,
        ResponseFactory $responseFactory,
        Translator $translator,
        AnnounceManager $announceManager
    ) {
        $this->encoder = $encoder;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->announceManager = $announceManager;
    }

    public function show(Request $request): Response
    {
        $passkey = $request->input('passkey');

        if (empty($passkey) || 64 !== strlen($passkey)) {
            return $this->getErrorResponse($this->translator->trans('messages.announce.invalid_passkey'));
        }

        $user = $this->announceManager->getUser($passkey);

        if (null === $user) {
            return $this->getErrorResponse($this->translator->trans('messages.announce.invalid_passkey'));
        }

        if (true === (bool) $user->banned) {
            return $this->getErrorResponse($this->translator->trans('messages.announce.banned_user'));
        }

        $queryParameters = explode('&', $request->server->get('QUERY_STRING'));
        $infoHashes = [];

        foreach ($queryParameters as $parameter) {
            [$name, $value] = explode('=', $parameter);
            $decodedName = urldecode($name);
            if ('info_hash' === $decodedName) {
                $infoHash = urldecode(trim($value));
                if (20 === strlen($infoHash)) {
                    $infoHashes[] = $infoHash;
                }
            }
        }

        return $this->responseFactory->make(
            $this->announceManager->scrape($infoHashes)
        )->header('Content-Type', 'text/plain');
    }

    private function getErrorResponse(string $message): Response
    {
        return $this->responseFactory->make(
            $this->encoder->encode(['failure reason' => $message, 'retry in' => 'never'])
        )->header('Content-Type', 'text/plain');
    }
}
