<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Bencoder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AnnounceManager;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Routing\ResponseFactory;

class ScrapeController extends Controller
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
     * @param Bencoder        $encoder
     * @param ResponseFactory $responseFactory
     */
    public function __construct(Bencoder $encoder, ResponseFactory $responseFactory)
    {
        $this->encoder = $encoder;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param Request         $request
     * @param Translator      $translator
     * @param AnnounceManager $announceManager
     *
     * @return Response
     */
    public function show(Request $request, Translator $translator, AnnounceManager $announceManager): Response
    {
        $passkey = $request->input('passkey');

        if (true !== $request->filled('passkey') || 64 !== strlen($passkey)) {
            return $this->getErrorResponse($translator->trans('messages.announce.invalid_passkey'));
        }

        $user = $announceManager->getUser($passkey);

        if (null === $user) {
            return $this->getErrorResponse($translator->trans('messages.announce.invalid_passkey'));
        }

        if (true === (bool) $user->banned) {
            return $this->getErrorResponse($translator->trans('messages.announce.banned_user'));
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
            $announceManager->scrape($infoHashes)
        )->header('Content-Type', 'text/plain');
    }

    /**
     * @param string $message
     *
     * @return Response
     */
    private function getErrorResponse(string $message): Response
    {
        return $this->responseFactory->make(
            $this->encoder->encode(['failure reason' => $message, 'retry in' => 'never'])
        )->header('Content-Type', 'text/plain');
    }
}
