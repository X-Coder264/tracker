@extends('layouts.app')

@section('content')
    <div class="col-12 mx-auto">
        @include('partials.flash_messages')

        <div class="card">
            <div class="card-header">
                <a href="{{ route('torrents.download', $torrent) }}" class="btn btn-primary">{{ $torrent->name }}</a>
            </div>

            <div class="card-body">
                {!! nl2br($torrent->description, false) !!}
            </div>
        </div>

        <br>

        <div class="card">
            <div class="card-body">
                <div id="accordion" role="tablist" aria-multiselectable="true">
                    <div class="card">
                        <div class="card-header" role="tab" id="peers">
                            <h5 class="mb-0">
                                <a data-toggle="collapse" data-parent="#accordion" href="#peersTable" aria-expanded="true" aria-controls="peersTable">
                                    {{ trans('messages.torrents.show.peers') }} ({{ $numberOfPeers }})
                                </a>
                            </h5>
                        </div>
                        <div class="collapse" id="peersTable" role="tabpanel" aria-labelledby="peers">
                            @if (0 === $numberOfPeers)
                                <div class="card-body">
                                    {{ trans('messages.torrents.show.no_peers') }}
                                </div>
                            @else
                                <div class="table-responsive-lg">
                                    <table class="table table-hover table-bordered">
                                        <thead>
                                        <tr>
                                            <th>{{ trans('messages.common.torrent_table_username') }}</th>
                                            <th>{{ trans('messages.common.torrent_table_uploaded') }}</th>
                                            <th>{{ trans('messages.common.torrent_table_downloaded') }}</th>
                                            <th>{{ trans('messages.common.torrent_table_ratio') }}</th>
                                            <th>{{ trans('messages.common.torrent_table_last_announce') }}</th>
                                            <th>{{ trans('messages.common.torrent_table_torrent_client') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($torrent->peers as $peer)
                                            <tr>
                                                <td>{{ $peer->user->name }}</td>
                                                <td>{{ $peer->uploaded }}</td>
                                                <td>{{ $peer->downloaded }}</td>
                                                @if (0 === (int) $peer->getOriginal('downloaded'))
                                                    <td>Inf.</td>
                                                @else
                                                    <td>{{ number_format((int) $peer->getOriginal('uploaded') / (int) $peer->getOriginal('downloaded'), 2) }}</td>
                                                @endif
                                                <td>{{ $peer->updated_at->diffForHumans() }}</td>
                                                <td>{{ $peer->userAgent }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" role="tab" id="files">
                            <h5 class="mb-0">
                                <a data-toggle="collapse" data-parent="#accordion" href="#filesTable" aria-expanded="true" aria-controls="filesTable">
                                   {{ trans('messages.torrents.show.files') }} ({{ $filesCount }})
                                </a>
                            </h5>
                        </div>
                        <div class="collapse" id="filesTable" role="tabpanel" aria-labelledby="files">
                            @if (0 === $filesCount)
                                {{ trans('messages.common.error') }}
                            @else
                                <div class="table-responsive-lg">
                                    <table class="table table-hover table-bordered">
                                        <thead>
                                        <tr>
                                            <th>{{ trans('messages.torrents.show.file_name') }}</th>
                                            <th>{{ trans('messages.common.size') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($torrentFileNamesAndSizes as $file)
                                            <tr>
                                                <td>{{ $file[0] }}</td>
                                                <td>{{ $file[1] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrents.show.torrent_comments') }}
            </div>

            <div class="card-body">
                @if (session('torrentCommentSuccess'))
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h4 class="card-title">{{ trans('messages.flash_messages.success') }}</h4>
                            <p class="card-text">{{ session('torrentCommentSuccess') }}</p>
                        </div>
                    </div>
                    <br>
                @endif
                <a href="{{ route('torrent-comments.create', $torrent) }}" class="btn btn-primary btn-block">{{ trans('messages.torrents.show.add_comment') }}</a><br>
                @if ($torrentComments->isEmpty())
                        <div class="card text-dark bg-warning">
                            <div class="card-body">
                                <p class="card-text">{{ trans('messages.torrents.show.no_comments') }}</p>
                            </div>
                        </div>
                @else
                    @foreach ($torrentComments as $torrentComment)
                        @include('partials.torrent_comment', ['torrentComment' => $torrentComment])
                    @endforeach
                    <br>
                    {{ $torrentComments->render() }}
                @endif
            </div>
        </div>
    </div>
@endsection
