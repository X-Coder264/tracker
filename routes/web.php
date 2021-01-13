<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\IndexController as AdminIndexController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Invites\CreateController as InviteCreateController;
use App\Http\Controllers\Invites\StoreController as InviteStoreController;
use App\Http\Controllers\PrivateMessages\ThreadMessages\CreateController as PrivateMessageCreateController;
use App\Http\Controllers\PrivateMessages\ThreadMessages\StoreController as PrivateMessageStoreController;
use App\Http\Controllers\PrivateMessages\Threads\IndexController as PrivateMessageThreadsIndexController;
use App\Http\Controllers\PrivateMessages\Threads\ShowController as PrivateMessageThreadsShowController;
use App\Http\Controllers\RSS\TorrentFeedController;
use App\Http\Controllers\RSS\UserTorrentFeed\ShowController as UserTorrentRSSFeedShowController;
use App\Http\Controllers\RSS\UserTorrentFeed\StoreController as UserTorrentRSSFeedStoreController;
use App\Http\Controllers\Snatches\ShowTorrentSnatchesController;
use App\Http\Controllers\Snatches\ShowUserSnatchesController;
use App\Http\Controllers\TorrentComments\CreateController as TorrentCommentCreateController;
use App\Http\Controllers\TorrentComments\EditController as TorrentCommentEditController;
use App\Http\Controllers\TorrentComments\StoreController as TorrentCommentStoreController;
use App\Http\Controllers\TorrentComments\UpdateController as TorrentCommentUpdateController;
use App\Http\Controllers\Torrents\CreateController as TorrentCreateController;
use App\Http\Controllers\Torrents\DownloadController;
use App\Http\Controllers\Torrents\DownloadSeedingTorrentsZipArchiveController;
use App\Http\Controllers\Torrents\DownloadSnatchedTorrentsZipArchiveController;
use App\Http\Controllers\Torrents\EditController as TorrentEditController;
use App\Http\Controllers\Torrents\IndexController as TorrentIndexController;
use App\Http\Controllers\Torrents\ShowController as TorrentShowController;
use App\Http\Controllers\Torrents\StoreController as TorrentStoreController;
use App\Http\Controllers\Torrents\UpdateController as TorrentUpdateController;
use App\Http\Controllers\TwoFactorAuth\DisableController;
use App\Http\Controllers\TwoFactorAuth\EnableController;
use App\Http\Controllers\TwoFactorAuth\StatusController;
use App\Http\Controllers\Users\EditController as UserEditController;
use App\Http\Controllers\Users\ShowController as UserShowController;
use App\Http\Controllers\Users\UpdateController as UserUpdateController;
use App\Http\Controllers\UserTorrents\ShowLeechingTorrentsController;
use App\Http\Controllers\UserTorrents\ShowSeedingTorrentsController;
use App\Http\Controllers\UserTorrents\ShowUploadedTorrentsController;
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
$router->get('login', 'App\Http\Controllers\Auth\LoginController@showLoginForm')->name('login');
$router->post('login', 'App\Http\Controllers\Auth\LoginController@login');
$router->post('logout', 'App\Http\Controllers\Auth\LoginController@logout')->name('logout');

// Registration Routes...
$router->get('register', 'App\Http\Controllers\Auth\RegisterController@showRegistrationForm')->name('register');
$router->post('register', 'App\Http\Controllers\Auth\RegisterController@register');

// 2FA Routes...
$router->get('login/2fa', 'App\Http\Controllers\Auth\TwoFactorStepController@showTwoFactorStep')->name('2fa.show_form');
$router->post('login/2fa', 'App\Http\Controllers\Auth\TwoFactorStepController@verifyTwoFactorStep')->name('2fa.verify');

// Password Reset Routes...
$router->get('password/reset', 'App\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
$router->post('password/email', 'App\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
$router->get('password/reset/{token}', 'App\Http\Controllers\Auth\ResetPasswordController@showResetForm')->name('password.reset');
$router->post('password/reset', 'App\Http\Controllers\Auth\ResetPasswordController@reset')->name('password.update');

$router->get('torrents/download/{torrent}', DownloadController::class)->name('torrents.download');

$router->get('rss/{passkey}/torrents', TorrentFeedController::class)->name('torrents.rss');

$router->group(['middleware' => [Authenticate::class]], function (Registrar $router) {
    $router->get('/', HomeController::class)->name('home');

    $router->get('/cms', AdminIndexController::class)->name('admin.index');
    // catch all route for the admin CMS (except the API ones), we are leaving the routing for the CMS to the frontend router
    $router->get('/cms/{all}', AdminIndexController::class)->where(['all' => '^(?!api).*$']);

    $router->get('torrents', TorrentIndexController::class)->name('torrents.index');
    $router->get('torrents/create', TorrentCreateController::class)->name('torrents.create');
    $router->get('torrents/{torrent}', TorrentShowController::class)->name('torrents.show');
    $router->get('torrents/{torrent}/edit', TorrentEditController::class)->name('torrents.edit');
    $router->put('torrents/{torrent}', TorrentUpdateController::class)->name('torrents.update');
    $router->post('torrents', TorrentStoreController::class)->name('torrents.store');

    $router->get('torrents-snatched-archive-download', DownloadSnatchedTorrentsZipArchiveController::class)->name('torrents.download-snatched-archive');
    $router->get('torrents-seeding-archive-download', DownloadSeedingTorrentsZipArchiveController::class)->name('torrents.download-seeding-archive');

    $router->get('torrent-comments/{torrent}/create', TorrentCommentCreateController::class)->name('torrent-comments.create');
    $router->get('torrent-comments/{torrentComment}', TorrentCommentEditController::class)->name('torrent-comments.edit');
    $router->post('torrent-comments/{torrent}', TorrentCommentStoreController::class)->name('torrent-comments.store');
    $router->put('torrent-comments/{torrentComment}', TorrentCommentUpdateController::class)->name('torrent-comments.update');

    $router->get('torrents/{torrent}/snatches', ShowTorrentSnatchesController::class)->name('snatches.show');

    $router->get('users/{user}/snatches', ShowUserSnatchesController::class)->name('user-snatches.show');

    $router->get('users/{user}/uploaded-torrents', ShowUploadedTorrentsController::class)->name('user-torrents.show-uploaded-torrents');
    $router->get('users/{user}/seeding-torrents', ShowSeedingTorrentsController::class)->name('user-torrents.show-seeding-torrents');
    $router->get('users/{user}/leeching-torrents', ShowLeechingTorrentsController::class)->name('user-torrents.show-leeching-torrents');

    $router->get('users/{user}/edit', UserEditController::class)->name('users.edit');
    $router->get('users/rss', UserTorrentRSSFeedShowController::class)->name('users.rss.show');
    $router->post('users/rss', UserTorrentRSSFeedStoreController::class)->name('users.rss.store');
    $router->get('users/{user}', UserShowController::class)->name('users.show');
    $router->put('users/{user}', UserUpdateController::class)->name('users.update');

    $router->get('threads', PrivateMessageThreadsIndexController::class)->name('threads.index');
    $router->get('threads/{thread}/message/create', PrivateMessageCreateController::class)->name('thread-messages.create');
    $router->post('threads/{thread}/message', PrivateMessageStoreController::class)->name('thread-messages.store');
    $router->get('threads/{thread}', PrivateMessageThreadsShowController::class)->name('threads.show');

    $router->get('invites/create', InviteCreateController::class)->name('invites.create');
    $router->post('invites', InviteStoreController::class)->name('invites.store');

    $router->get('2fa', StatusController::class)->name('2fa.status');
    $router->post('2fa-enable', EnableController::class)->name('2fa.enable');
    $router->post('2fa-disable', DisableController::class)->name('2fa.disable');
});
