@extends('layouts.app')

@section('content')
@include('partials.flash_messages')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">{{ trans('messages.home.page_title') }}</div>
            <div class="card-body">
                {{ trans('messages.home.no_news') }}
            </div>
        </div>
    </div>
</div>

<br>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">{{ trans('messages.home.page_statistics') }}</div>
            <div class="card-body">
                <div class="table-responsive-lg">
                    <table class="table table-hover table-bordered">
                        <tbody>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_user_count') }}</th>
                                <td>{{ $usersCount }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_banned_user_count') }}</th>
                                <td>{{ $bannedUsersCount }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_peer_count') }}</th>
                                <td>{{ $peersCount }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_seeder_count') }}</th>
                                <td>{{ $seedersCount }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_leecher_count') }}</th>
                                <td>{{ $leechersCount }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_torrent_count') }}</th>
                                <td>{{ $torrentsCount }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_torrent_size') }}</th>
                                <td>{{ $totalTorrentSize }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.home.page_statistics_dead_torrent_count') }}</th>
                                <td>{{ $deadTorrentsCount }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="card-text text-center">{{ trans('messages.home.page_statistics_note') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
