<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AnnounceValidationException;
use App\Presenters\Announce\AnnounceRequest;
use App\Services\Announce\AnnounceManager;
use App\Services\Announce\ErrorResponseFactory;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AnnounceController
{
    private AnnounceManager $announceManager;
    private ValidationFactory $validationFactory;
    private Translator $translator;
    private ErrorResponseFactory $errorResponseFactory;
    private ResponseFactory $responseFactory;

    public function __construct(
        AnnounceManager $announceManager,
        ValidationFactory $validationFactory,
        Translator $translator,
        ErrorResponseFactory $errorResponseFactory,
        ResponseFactory $responseFactory
    ) {
        $this->announceManager = $announceManager;
        $this->validationFactory = $validationFactory;
        $this->translator = $translator;
        $this->errorResponseFactory = $errorResponseFactory;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $announceRequest = AnnounceRequest::fromHttpRequest($request, $this->validationFactory, $this->translator);
        } catch (AnnounceValidationException $exception) {
            $validationData = $exception->getValidationMessages() ?: $exception->getMessage();

            return $this->getResponse($this->errorResponseFactory->create($validationData));
        }

        return $this->getResponse($this->announceManager->announce($announceRequest));
    }

    private function getResponse(string $content): Response
    {
        return $this->responseFactory->make($content)->header('Content-Type', 'text/plain');
    }
}
