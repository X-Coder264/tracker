@extends('layouts.app')

@section('content')
    <div class="col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrent.index.page_title') }}
            </div>

            <div class="card-body">
                @if ($torrents->isEmpty())
                    <div class="card text-dark bg-warning">
                        <div class="card-body">
                            <h4 class="card-title">{{ trans('messages.torrent.index.notice_no_active_torrents_title') }}</h4>
                            <p class="card-text">{{ trans('messages.torrent.index.notice_no_active_torrents_text') }}</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive-lg">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ trans('messages.torrent.index.table_category') }}</th>
                                    <th>{{ trans('messages.torrent.index.table_torrent_name') }}</th>
                                    <th>{{ trans('messages.common.size') }}</th>
                                    <th>{{ trans('messages.torrent.index.table_torrent_seeders') }}</th>
                                    <th>{{ trans('messages.torrent.index.table_torrent_leechers') }}</th>
                                    <th>{{ trans('messages.torrent.index.table_torrent_uploader') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($torrents as $torrent)
                                    <tr>
                                        <td>XYZ</td>
                                        <td>
                                            <a href="{{ route('torrents.show', $torrent) }}">{{ $torrent->name }}</a>
                                            <br>
                                            {{ $torrent->created_at->timezone($timezone) }}
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
                    </div>
                    {{ $torrents->render() }}
                @endif
            </div>
        </div>
    </div>
@endsection
