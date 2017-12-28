<?php

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

Auth::routes();
Route::get('announce', 'AnnounceController@store')->name('announce');

Route::middleware(['auth'])->group(function () {
    Route::get('/', 'HomeController@index')->name('home.index');

    Route::get('torrents', 'TorrentController@index')->name('torrents.index');
    Route::get('torrents/create', 'TorrentController@create')->name('torrents.create');
    Route::get('torrents/{torrent}', 'TorrentController@show')->name('torrents.show');
    Route::post('torrents/upload', 'TorrentController@store')->name('torrents.store');
    Route::get('torrents/download/{torrent}', 'TorrentController@download')->name('torrents.download');

    Route::get('torrent-comments/{torrent}/create', 'TorrentCommentController@create')->name('torrent-comments.create');
    Route::post('torrent-comments/{torrent}', 'TorrentCommentController@store')->name('torrent-comments.store');

    Route::get('users/{user}/edit', 'UserController@edit')->name('users.edit');
    Route::put('users/{user}', 'UserController@update')->name('users.update');
});
