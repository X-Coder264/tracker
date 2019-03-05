<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Announce\DataFactory;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Services\Announce\Manager as AnnounceManager;

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

    public function __construct(
        AnnounceManager $announceManager,
        ResponseFactory $responseFactory,
        DataFactory $dataFactory
    ) {
        $this->announceManager = $announceManager;
        $this->responseFactory = $responseFactory;
        $this->dataFactory = $dataFactory;
    }

    public function store(Request $request): Response
    {
//        try {
//            $data = $this->dataFactory->makeFromRequest($request);
//        } catch (AnnounceValidationException $exception) {
//            $validationData = $exception->getValidationMessages() ?: $exception->getMessage();
//
//            return $this->announceErrorResponse($validationData);
//        }

        return $this
            ->responseFactory
            ->make(
                $this->announceManager->announce($request)
            )
            ->header('Content-Type', 'text/plain');
    }
}
