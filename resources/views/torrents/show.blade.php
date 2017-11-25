@extends('layouts.app')

@section('content')
    <div class="col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('torrents.download', $torrent) }}">{{ $torrent->name }}</a>
            </div>

            <div class="card-body">
                @include('partials.flash_messages')
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
                                    {{ __('messages.torrents.show.peers') }} ({{ $numberOfPeers }})
                                </a>
                            </h5>
                        </div>
                        <div class="collapse" id="peersTable" role="tabpanel" aria-labelledby="peers">
                            @if($numberOfPeers === 0)
                                <div class="card-body">
                                    {{ __('messages.torrents.show.no_peers') }}
                                </div>
                            @else
                                <table class="table table-hover table-bordered table-responsive-sm table-responsive-md table-responsive-lg">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Uploaded</th>
                                            <th>Downloaded</th>
                                            <th>Ratio</th>
                                            <th>Last announce</th>
                                            <th>Client</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($torrent->peers as $peer)
                                            <tr>
                                                <td>{{ $peer->user->name }}</td>
                                                <td>{{ $peer->uploaded }}</td>
                                                <td>{{ $peer->downloaded }}</td>
                                                @if($peer->downloaded == 0)
                                                    <td>Inf.</td>
                                                @else
                                                    <td>{{ number_format((int) $peer->uploaded / (int) $peer->downloaded, 2) }}</td>
                                                @endif
                                                <td>{{ $peer->updated_at->diffForHumans() }}</td>
                                                <td>{{ $peer->userAgent }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" role="tab" id="files">
                            <h5 class="mb-0">
                                <a data-toggle="collapse" data-parent="#accordion" href="#filesTable" aria-expanded="true" aria-controls="filesTable">
                                   {{ __('messages.torrents.show.files') }} ({{ count($torrentFileNamesAndSizes) }})
                                </a>
                            </h5>
                        </div>
                        <div class="collapse" id="filesTable" role="tabpanel" aria-labelledby="files">
                            @if(count($torrentFileNamesAndSizes) === 0)
                                Error.
                            @else
                                <table class="table table-hover table-bordered table-responsive-sm table-responsive-md table-responsive-lg">
                                    <thead>
                                        <tr>
                                            <th>{{ __('messages.torrents.show.file_name') }}</th>
                                            <th>{{ __('messages.common.size') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($torrentFileNamesAndSizes as $file)
                                        <tr>
                                            <td>{{ $file[0] }}</td>
                                            <td>{{ $file[1] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection