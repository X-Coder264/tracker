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
     * @param Request         $request
     * @param AnnounceManager $announceManager
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function store(Request $request, AnnounceManager $announceManager, ResponseFactory $responseFactory): Response
    {
        return $responseFactory->make($announceManager->announce($request))->header('Content-Type', 'text/plain');
    }
}
