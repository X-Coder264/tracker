@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive-lg">
                    <table class="table table-hover table-bordered">
                        <tbody>
                            <tr>
                                <th>{{ trans('messages.user.profile_last_seen_at') }}</th>
                                <td>
                                    @if (null === $user->last_seen_at)
                                        {{ trans('messages.user.profile_last_seen_at_never') }}
                                    @else
                                        {{ $user->last_seen_at->timezone($timezone)->format('d.m.Y. H:i') }} ({{ $user->last_seen_at->diffForHumans() }})
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.user.profile_registered_at') }}</th>
                                <td>{{ $user->created_at->timezone($timezone)->format('d.m.Y. H:i') }} ({{ $user->created_at->diffForHumans() }})</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.total_seeding_size') }}</th>
                                <td>{{ $totalSeedingSize }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.uploaded') }}</th>
                                <td>{{ $user->uploaded }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.downloaded') }}</th>
                                <td>{{ $user->downloaded }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.user.profile_count_of_uploaded_torrents') }}</th>
                                <td>
                                    @if (0 === $uploadedTorrentsCount)
                                        0
                                    @else
                                        <a href="{{ route('user-torrents.show-uploaded-torrents', $user) }}">{{ $uploadedTorrentsCount }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.seeding') }}</th>
                                <td>
                                    @if (0 === $seedingTorrentPeersCount)
                                        0
                                    @else
                                        <a href="{{ route('user-torrents.show-seeding-torrents', $user) }}">{{ $seedingTorrentPeersCount }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.leeching') }}</th>
                                <td>
                                    @if (0 === $leechingTorrentPeersCount)
                                        0
                                    @else
                                        <a href="{{ route('user-torrents.show-leeching-torrents', $user) }}">{{ $leechingTorrentPeersCount }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.user.profile_snatches') }}</th>
                                <td>
                                    @if (0 === $snatchesCount)
                                        0
                                    @else
                                        <a href="{{ route('user-snatches.show', $user) }}">{{ $snatchesCount }}</a>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
