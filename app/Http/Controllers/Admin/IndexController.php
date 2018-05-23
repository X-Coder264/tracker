<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use DateTimeZone;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

class IndexController extends Controller
{
    /**
     * @param AuthManager     $authManager
     * @param Repository      $configRepository
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function index(AuthManager $authManager, Repository $configRepository, ResponseFactory $responseFactory): Response
    {
        $user = $authManager->guard()->user();
        $projectName = $configRepository->get('app.name');
        $enumerations = json_encode(['timezones' => (object) DateTimeZone::listIdentifiers()]);

        return $responseFactory->view('admin.index', compact('user', 'projectName', 'enumerations'));
    }
}
