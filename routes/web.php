<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/** @var Router $router */
$router->auth();
$router->get('announce', 'AnnounceController@store')->name('announce');

Route::middleware(['auth'])->group(function () use ($router) {
    $router->get('/', 'HomeController@index')->name('home');

    $router->get('/cms', 'Admin\IndexController@index')->name('admin.index');
    // catch all route for the admin CMS (except the API ones), we are leaving the routing for the CMS to the frontend router
    $router->get('/cms/{all}', 'Admin\IndexController@index')->where(['all' => '^(?!api).*$']);

    $router->get('torrents', 'TorrentController@index')->name('torrents.index');
    $router->get('torrents/create', 'TorrentController@create')->name('torrents.create');
    $router->get('torrents/download/{torrent}', 'TorrentController@download')->name('torrents.download');
    $router->get('torrents/{torrent}', 'TorrentController@show')->name('torrents.show');
    $router->post('torrents', 'TorrentController@store')->name('torrents.store');

    $router->get('torrent-comments/{torrent}/create', 'TorrentCommentController@create')->name('torrent-comments.create');
    $router->get('torrent-comments/{torrentComment}', 'TorrentCommentController@edit')->name('torrent-comments.edit');
    $router->post('torrent-comments/{torrent}', 'TorrentCommentController@store')->name('torrent-comments.store');
    $router->put('torrent-comments/{torrentComment}', 'TorrentCommentController@update')->name('torrent-comments.update');

    $router->get('users/{user}/edit', 'UserController@edit')->name('users.edit');
    $router->get('users/{user}', 'UserController@show')->name('users.show');
    $router->put('users/{user}', 'UserController@update')->name('users.update');
});
