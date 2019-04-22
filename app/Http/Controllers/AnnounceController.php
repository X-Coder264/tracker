<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Announce\DataFactory;
use App\Exceptions\ValidationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Services\Announce\Manager as AnnounceManager;
use App\Services\Announce\ResponseFactory as AnnounceResponseFactory;
use Illuminate\Translation\Translator;

class AnnounceController
{
    /**
     * @var AnnounceManager
     */
    private $announceManager;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var AnnounceResponseFactory
     */
    private $announceResponseFactory;

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(
        AnnounceManager $announceManager,
        ResponseFactory $responseFactory,
        DataFactory $dataFactory,
        AnnounceResponseFactory $announceResponseFactory,
        UserRepositoryInterface $userRepository,
        Translator $translator
    ) {
        $this->announceManager = $announceManager;
        $this->responseFactory = $responseFactory;
        $this->dataFactory = $dataFactory;
        $this->announceResponseFactory = $announceResponseFactory;
        $this->userRepository = $userRepository;
        $this->translator = $translator;
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->dataFactory->makeFromRequest($request);

            $passkey = $data->getPassKey();

            $user = $this->userRepository->getUserByPassKey($passkey);

            if (null === $user) {
                throw ValidationException::single($this->translator->trans('messages.announce.invalid_passkey'), true);
            }

            if ($user->isBanned()) {
                throw ValidationException::single($this->translator->trans('messages.announce.banned_user'), true);
            }

            $response = $this->announceManager->announce($data, $user, CarbonImmutable::now());
        } catch (ValidationException $exception) {
            return $this->announceResponseFactory->validationError($exception);
        }

        $compact = $request->input('compact');
        // return compact response if the client wants a compact response or if the client did not
        // specify what kind of response it wants, else return non-compact response
        if (null === $compact || 1 === (int) $compact) {
            return $this->announceResponseFactory->compactSuccess($response);
        }

        return $this->announceResponseFactory->nonCompactSuccess($response);
    }
}
