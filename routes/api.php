<?php

declare(strict_types=1);

use Illuminate\Contracts\Routing\Registrar;
use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;

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

JsonApi::register('default', ['namespace' => 'Admin'], function (ApiGroup $api, Registrar $router) {
    $api->resource('users');
    $api->resource('locales');
    $api->resource('torrents');
});
