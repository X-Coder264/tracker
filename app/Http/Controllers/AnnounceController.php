<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Services\AnnounceService;
use Illuminate\Http\Request;

class AnnounceController extends Controller
{
    public function store(Request $request, AnnounceService $announceService)
    {
        //Storage::put('filename2.txt', print_r($request->all(), true));
        return $announceService->announce($request);
    }
}
