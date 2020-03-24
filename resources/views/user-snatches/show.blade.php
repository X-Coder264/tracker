@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrent-snatches.header') }} - <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
            </div>

            <div class="card-body">
                @if ($snatches->isEmpty())
                    <div class="card text-dark bg-warning">
                        <div class="card-body">
                            <h4 class="card-title">{{ trans('messages.common.notice') }}</h4>
                            <p class="card-text">{{ trans('messages.notice.no-snatches') }}</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive-lg">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ trans('messages.common.category') }}</th>
                                    <th>{{ trans('messages.common.torrent') }}</th>
                                    <th>{{ trans('messages.common.size') }}</th>
                                    <th>{{ trans('messages.common.torrent_seeders') }}</th>
                                    <th>{{ trans('messages.common.torrent_leechers') }}</th>
                                    <th>{{ trans('messages.common.uploaded') }}</th>
                                    <th>{{ trans('messages.common.downloaded') }}</th>
                                    <th>{{ trans('messages.common.torrent_table_ratio') }}</th>
                                    <th>{{ trans('messages.common.seed_time') }}</th>
                                    <th>{{ trans('messages.common.leech_time') }}</th>
                                    <th>{{ trans('messages.common.torrent_table_last_announce') }}</th>
                                    <th>{{ trans('messages.common.torrent_table_torrent_client') }}</th>
                                    <th>{{ trans('messages.torrent-snatches.finished_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($snatches as $snatch)
                                    <tr>
                                        <td>{{ $snatch->torrent->category->name }}</td>
                                        <td>
                                            <a href="{{ route('torrents.show', $snatch->torrent) }}">{{ $snatch->torrent->name }}</a>
                                            <br>
                                            {{ $snatch->torrent->created_at->timezone($timezone) }}
                                            {{ $snatch->torrent->created_at->diffForHumans() }}
                                        </td>
                                        <td>{{ $snatch->torrent->size }}</td>
                                        <td>{{ $snatch->torrent->seeders }}</td>
                                        <td>{{ $snatch->torrent->leechers }}</td>
                                        <td>{{ $snatch->uploaded }}</td>
                                        <td>{{ $snatch->downloaded }}</td>
                                        @if (0 === (int) $snatch->getRawOriginal('downloaded'))
                                            <td>Inf.</td>
                                        @else
                                            <td>{{ number_format((int) $snatch->getRawOriginal('uploaded') / (int) $snatch->getRawOriginal('downloaded'), 2) }}</td>
                                        @endif
                                        <td>{{ $snatch->seedTime }}</td>
                                        <td>{{ $snatch->leechTime }}</td>
                                        <td>{{ $snatch->updated_at->diffForHumans() }}</td>
                                        <td>{{ $snatch->userAgent }}</td>
                                        <td>
                                            @if (null !== $snatch->finished_at)
                                                {{ $snatch->finished_at->timezone($timezone) }}
                                            @else
                                                -
                                            @endif
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
