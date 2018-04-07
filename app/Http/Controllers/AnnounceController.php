<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AnnounceManager;

class AnnounceController extends Controller
{
    /**
     * @param Request          $request
     * @param $announceManager $announceManager
     *
     * @return Response
     */
    public function store(Request $request, AnnounceManager $announceManager): Response
    {
        return response($announceManager->announce($request))->header('Content-Type', 'text/plain');
    }
}
