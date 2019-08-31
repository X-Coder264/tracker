<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use DateTimeZone;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;

final class IndexController
{
    public function __invoke(Guard $guard, Repository $config, ResponseFactory $responseFactory): Response
    {
        $user = $guard->user();
        $projectName = $config->get('app.name');
        $enumerations = json_encode(['timezones' => (object) DateTimeZone::listIdentifiers()]);

        return $responseFactory->view('admin.index', compact('user', 'projectName', 'enumerations'));
    }
}
