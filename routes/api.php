<?php

declare(strict_types=1);

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

JsonApi::register('default', ['namespace' => 'Admin'], function ($api, $router) {
    $api->resource('users');
    $api->resource('locales');
    $api->resource('torrents');
});
