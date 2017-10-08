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
                    <table class="table table-hover table-responsive-sm table-responsive-md table-responsive-lg">
                        <thead>
                        <tr>
                            <th>{{ __('messages.torrent.index.table_category') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_name') }}</th>
                            <th>{{ __('messages.common.size') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_seeders') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_leechers') }}</th>
                            <th>{{ __('messages.torrent.index.table_torrent_uploader') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($torrents as $torrent)
                            <tr class="bg-warning">
                                <th>XYZ</th>
                                <td>
                                    <a href="{{ route('torrents.show', $torrent) }}">{{ $torrent->name }}</a>
                                    <br>
                                    {{ $torrent->created_at->timezone($timezone) }}
                                    @php
                                        \Carbon\Carbon::setLocale($userLocale);
                                    @endphp
                                    {{ $torrent->created_at->diffForHumans() }}
                                </td>
                                <td>{{ $torrent->size }}</td>
                                <td>{{ $torrent->seeders }}</td>
                                <td>{{ $torrent->leechers }}</td>
                                <td>{{ $torrent->uploader->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    {{ $torrents->render() }}
                @endif
            </div>
        </div>
    </div>
@endsection