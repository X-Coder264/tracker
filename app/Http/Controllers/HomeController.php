<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Contracts\Routing\ResponseFactory;

class HomeController extends Controller
{
    /**
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function index(ResponseFactory $responseFactory): Response
    {
        return $responseFactory->view('home.index');
    }
}
