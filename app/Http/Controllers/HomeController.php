<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Models\Configuration;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        dd(Configuration::all());
        return response()->view('home.index');
    }
}
