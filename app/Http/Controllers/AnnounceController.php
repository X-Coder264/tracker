<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AnnounceManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AnnounceController
{
    /**
     * @var AnnounceManager
     */
    private $announceManager;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(AnnounceManager $announceManager, ResponseFactory $responseFactory)
    {
        $this->announceManager = $announceManager;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request): Response
    {
        return $this->responseFactory->make(
            $this->announceManager->announce($request)
        )->header('Content-Type', 'text/plain');
    }
}
