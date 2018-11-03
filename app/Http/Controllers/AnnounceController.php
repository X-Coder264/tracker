<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AnnounceManager;
use Illuminate\Contracts\Routing\ResponseFactory;

class AnnounceController extends Controller
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

    public function store(Request $request): Response
    {
        return $this->responseFactory->make(
            $this->announceManager->announce($request)
        )->header('Content-Type', 'text/plain');
    }
}
