@extends('layouts.app')

@section('content')
    <div class="col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ __('messages.torrent.index.page_title') }}
            </div>

            <div class="card-body">
                @if($torrents->isEmpty())
                    <div class="card text-dark bg-warning">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('messages.torrent.index.notice_no_active_torrents_title') }}</h4>
                            <p class="card-text">{{ __('messages.torrent.index.notice_no_active_torrents_text') }}</p>
                        </div>
                    </div>
                @else
                    <table class="table table-hover table-responsive-sm">
                        <thead>
                        <tr>
                            <th>{{ __('messages.torrent.index.table_category') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_name') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_seeders') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_leechers') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_uploader') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($torrents as $torrent)
                            <tr class="bg-warning">
                                <th>XYZ</th>
                                <td><a href="{{ route('torrent.download', $torrent->slug) }}">{{ $torrent->name }}</a></td>
                                <td>{{ $torrent->seeders }}</td>
                                <td>{{ $torrent->leechers }}</td>
                                <td>{{ $torrent->uploader->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection