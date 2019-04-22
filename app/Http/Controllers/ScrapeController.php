<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\User\UserRepositoryInterface;
use App\Services\Bencoder;
use App\Services\Announce\Scrape\Manager as ScrapeManager;
use App\Services\Announce\Scrape\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\ValidationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Translation\Translator;
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
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;
    /**
     * @var ScrapeManager
     */
    private $scrapeManager;

    public function __construct(
        Bencoder $encoder,
        Translator $translator,
        AnnounceManager $announceManager,
        ConnectionInterface $connection,
        UserRepositoryInterface $userRepository,
        ScrapeManager $scrapeManager,
        ResponseFactory $responseFactory
    ) {
        $this->encoder = $encoder;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->announceManager = $announceManager;
        $this->connection = $connection;
        $this->userRepository = $userRepository;
        $this->scrapeManager = $scrapeManager;
    }

    public function show(Request $request): Response
    {
        $passkey = $request->input('passkey');

        if (empty($passkey) || 64 !== strlen($passkey)) {
            return $this->responseFactory->validationError(
                ValidationException::single(
                    $this->translator->trans('messages.announce.invalid_passkey'), true
                )
            );
        }

        $user = $this->userRepository->getUserByPassKey($passkey);

        if (null === $user) {
            return $this->responseFactory->validationError(
                ValidationException::single(
                    $this->translator->trans('messages.announce.invalid_passkey'), true
                )
            );
        }

        if ($user->isBanned()) {
            return $this->responseFactory->validationError(
                ValidationException::single(
                    $this->translator->trans('messages.announce.banned_user'), true
                )
            );
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

        $response = $this->scrapeManager->scrape(...$infoHashes);

        if(empty($response)){
            return $this->responseFactory->validationError(
                ValidationException::single(
                    $this->translator->trans('messages.scrape.no_torrents')
                )
            );
        }

        return $this->responseFactory->success($response);
    }
}
