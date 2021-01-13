<?php

declare(strict_types=1);

use App\Http\Controllers\AnnounceController;
use App\Http\Controllers\ScrapeController;
use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use Illuminate\Contracts\Routing\Registrar;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/** @var Registrar $router */

$router->get('announce', AnnounceController::class)->name('announce');
$router->get('scrape', ScrapeController::class)->name('scrape');

/** @var Registrar $router */
$router->group(['middleware' => ['auth:api']], function () {
    JsonApi::register('default', ['namespace' => 'Admin'], function (RouteRegistrar $registrar) {
        $registrar->resource('users');
        $registrar->resource('locales');
        $registrar->resource('torrents');
        $registrar->resource('news');
    });
});
