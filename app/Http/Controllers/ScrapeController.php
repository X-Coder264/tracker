<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Announce\Contracts\UserRepositoryInterface;
use App\Services\Announce\ErrorResponseFactory;
use App\Services\Announce\ScrapeManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ScrapeController
{
    private Translator $translator;
    private UserRepositoryInterface $userRepository;
    private ErrorResponseFactory $errorResponseFactory;
    private ScrapeManager $scrapeManager;
    private ResponseFactory $responseFactory;

    public function __construct(
        Translator $translator,
        UserRepositoryInterface $userRepository,
        ErrorResponseFactory $errorResponseFactory,
        ScrapeManager $scrapeManager,
        ResponseFactory $responseFactory
    ) {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->errorResponseFactory = $errorResponseFactory;
        $this->scrapeManager = $scrapeManager;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request): Response
    {
        $passkey = $request->input('passkey');

        if (empty($passkey) || 64 !== strlen($passkey)) {
            return $this->getErrorResponse($this->translator->get('messages.announce.invalid_passkey'));
        }

        $user = $this->userRepository->getUserFromPasskey($passkey);

        if (null === $user) {
            return $this->getErrorResponse($this->translator->get('messages.announce.invalid_passkey'));
        }

        if ($user->isBanned()) {
            return $this->getErrorResponse($this->translator->get('messages.announce.banned_user'));
        }

        $queryParameters = explode('&', $request->server('QUERY_STRING'));
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

        return $this->responseFactory->make(
            $this->scrapeManager->scrape($infoHashes)
        )->header('Content-Type', 'text/plain');
    }

    private function getErrorResponse(string $message): Response
    {
        return $this->responseFactory->make(
            $this->errorResponseFactory->create($message, true)
        )->header('Content-Type', 'text/plain');
    }
}
