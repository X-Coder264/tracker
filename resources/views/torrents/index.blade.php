@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrent.index.page_title') }}
            </div>

            <div class="card-body">
                @if ($torrents->isEmpty())
                    <div class="card text-dark bg-warning">
                        <div class="card-body">
                            <h4 class="card-title">{{ trans('messages.common.notice') }}</h4>
                            <p class="card-text">{{ trans('messages.torrent.index.notice_no_active_torrents_text') }}</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive-lg">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ trans('messages.common.category') }}</th>
                                    <th>{{ trans('messages.torrent.index.table_torrent_name') }}</th>
                                    <th>{{ trans('messages.common.size') }}</th>
                                    <th>{{ trans('messages.common.torrent_seeders') }}</th>
                                    <th>{{ trans('messages.common.torrent_leechers') }}</th>
                                    <th>{{ trans('messages.common.uploader') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($torrents as $torrent)
                                    @include('partials.torrent', ['$torrent' => $torrent])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $torrents->render() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
