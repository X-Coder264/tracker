<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\AnnounceService;

class AnnounceController extends Controller
{
    public function store(Request $request, AnnounceService $announceService)
    {
        return response($announceService->announce($request))->header('Content-Type', 'text/plain');
    }
}
