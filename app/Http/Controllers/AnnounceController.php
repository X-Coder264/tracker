<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Services\AnnounceService;

class AnnounceController extends Controller
{
    /**
     * @param Request         $request
     * @param AnnounceService $announceService
     *
     * @return Response
     */
    public function store(Request $request, AnnounceService $announceService): Response
    {
        return response($announceService->announce($request))->header('Content-Type', 'text/plain');
    }
}
