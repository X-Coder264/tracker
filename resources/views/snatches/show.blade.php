@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    {{ trans('messages.torrent-snatches.header') }} -
                    <a href="{{ route('torrents.show', $torrent) }}">
                        {{ $torrent->name }}
                    </a>
                </div>

                <div class="card-body">
                    @if ($snatches->isEmpty())
                        <div class="card text-dark bg-warning">
                            <div class="card-body">
                                <h4 class="card-title">{{ trans('messages.common.notice') }}</h4>
                                <p class="card-text">{{ trans('messages.torrent-snatches.no-snatcher-for-torrent') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="text-white bg-info">
                            <div class="card-body">
                                <p class="card-text text-center">{{ trans('messages.common.total') }}: {{ $snatches->total() }}</p>
                            </div>
                        </div>

                        <br>

                        <div class="table-responsive-lg">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ trans('messages.common.torrent_table_username') }}</th>
                                        <th>{{ trans('messages.common.uploaded') }}</th>
                                        <th>{{ trans('messages.common.downloaded') }}</th>
                                        <th>{{ trans('messages.common.seed_time') }}</th>
                                        <th>{{ trans('messages.common.leech_time') }}</th>
                                        <th>{{ trans('messages.common.left') }}</th>
                                        <th>{{ trans('messages.torrent-snatches.finished_at') }}</th>
                                        <th>{{ trans('messages.common.torrent_table_torrent_client') }}</th>
                                        <th>{{ trans('messages.common.updated_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($snatches as $snatch)
                                        <tr>
                                            <td>
                                                <a href="{{ route('users.show', $snatch->user) }}">{{ $snatch->user->name }}</a>
                                            </td>
                                            <td>{{ $snatch->uploaded }}</td>
                                            <td>{{ $snatch->downloaded }}</td>
                                            <td>{{ $snatch->seed_time }}</td>
                                            <td>{{ $snatch->leech_time }}</td>
                                            <td>{{ $snatch->left }}</td>
                                            <td>
                                                @if (null === $snatch->finished_at)
                                                    -
                                                @else
                                                    {{ $snatch->finished_at->timezone($timezone) }}
                                                    ({{ $snatch->finished_at->diffForHumans() }})
                                                @endif
                                            </td>
                                            <td>{{ $snatch->user_agent }}</td>
                                            <td>
                                                {{ $snatch->updated_at->timezone($timezone) }}
                                                ({{ $snatch->updated_at->diffForHumans() }})
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $snatches->render() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
