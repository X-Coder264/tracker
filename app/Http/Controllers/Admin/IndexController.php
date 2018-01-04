<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use DateTimeZone;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        $user = Auth::user();
        $projectName = config('app.name');
        $enumerations = json_encode(['timezones' => (object) DateTimeZone::listIdentifiers()]);

        return response()->view('admin.index', compact('user', 'projectName', 'enumerations'));
    }
}
