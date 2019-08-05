<?php

declare(strict_types=1);

use Illuminate\Contracts\Routing\Registrar;
use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;

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
$router->group(['middleware' => ['auth:api']], function () {
    JsonApi::register('default', ['namespace' => 'Admin'], function (RouteRegistrar $registrar) {
        $registrar->resource('users');
        $registrar->resource('locales');
        $registrar->resource('torrents');
    });
});
