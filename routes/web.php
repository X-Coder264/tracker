<?php

declare(strict_types=1);

use App\Http\Middleware\Authenticate;
use Illuminate\Contracts\Routing\Registrar;

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

/** @var Registrar $router */

// Authentication Routes...
$router->get('login', 'Auth\LoginController@showLoginForm')->name('login');
$router->post('login', 'Auth\LoginController@login');
$router->post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
$router->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
$router->post('register', 'Auth\RegisterController@register');

// Password Reset Routes...
$router->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
$router->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
$router->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
$router->post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

$router->get('announce', 'AnnounceController@store')->name('announce');
$router->get('scrape', 'ScrapeController@show')->name('scrape');

$router->get('torrents/download/{torrent}', 'TorrentController@download')->name('torrents.download');

$router->get('rss/{passkey}/torrents', 'RSS\TorrentFeedController@show')->name('torrents.rss');

$router->group(['middleware' => [Authenticate::class]], function (Registrar $router) {
    $router->get('/', 'HomeController@index')->name('home');

    $router->get('/cms', 'Admin\IndexController@index')->name('admin.index');
    // catch all route for the admin CMS (except the API ones), we are leaving the routing for the CMS to the frontend router
    $router->get('/cms/{all}', 'Admin\IndexController@index')->where(['all' => '^(?!api).*$']);

    $router->get('torrents', 'TorrentController@index')->name('torrents.index');
    $router->get('torrents/create', 'TorrentController@create')->name('torrents.create');
    $router->get('torrents/{torrent}', 'TorrentController@show')->name('torrents.show');
    $router->post('torrents', 'TorrentController@store')->name('torrents.store');

    $router->get('torrent-comments/{torrent}/create', 'TorrentCommentController@create')->name('torrent-comments.create');
    $router->get('torrent-comments/{torrentComment}', 'TorrentCommentController@edit')->name('torrent-comments.edit');
    $router->post('torrent-comments/{torrent}', 'TorrentCommentController@store')->name('torrent-comments.store');
    $router->put('torrent-comments/{torrentComment}', 'TorrentCommentController@update')->name('torrent-comments.update');

    $router->get('torrents/{torrent}/snatches', 'SnatchController@show')->name('snatches.show');

    $router->get('users/{user}/snatches', 'UserSnatchesController@show')->name('user-snatches.show');

    $router->get('users/{user}/uploaded-torrents', 'UserTorrentsController@showUploadedTorrents')->name('user-torrents.show-uploaded-torrents');
    $router->get('users/{user}/seeding-torrents', 'UserTorrentsController@showSeedingTorrents')->name('user-torrents.show-seeding-torrents');
    $router->get('users/{user}/leeching-torrents', 'UserTorrentsController@showLeechingTorrents')->name('user-torrents.show-leeching-torrents');

    $router->get('users/{user}/edit', 'UserController@edit')->name('users.edit');
    $router->get('users/rss', 'RSS\UserTorrentFeedController@show')->name('users.rss.show');
    $router->post('users/rss', 'RSS\UserTorrentFeedController@store')->name('users.rss.store');
    $router->get('users/{user}', 'UserController@show')->name('users.show');
    $router->put('users/{user}', 'UserController@update')->name('users.update');

    $router->get('threads', 'PrivateMessages\ThreadController@index')->name('threads.index');
    $router->get('threads/{thread}/message/create', 'PrivateMessages\ThreadMessageController@create')->name('thread-messages.create');
    $router->post('threads/{thread}/message', 'PrivateMessages\ThreadMessageController@store')->name('thread-messages.store');
    $router->get('threads/{thread}', 'PrivateMessages\ThreadController@show')->name('threads.show');
});
