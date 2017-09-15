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

    Route::get('browse', 'TorrentController@index')->name('torrent.index');
    Route::get('upload', 'TorrentController@create')->name('torrent.create');
    Route::post('upload', 'TorrentController@store')->name('torrent.store');
    Route::get('download/{torrent}', 'TorrentController@download')->name('torrent.download');
});
