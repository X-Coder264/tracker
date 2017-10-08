@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">
            {{ __('messages.torrent.create.page_title') }}
        </div>

        <div class="card-body">
            @include('partials.flash_messages')
            <br>
            <form enctype="multipart/form-data" method="POST" action="{{ route('torrents.store') }}">
                {{ csrf_field() }}
                <div class="form-group row">
                    <label for="torrent" class="col custom-file">
                        <input type="file" id="torrent" name="torrent" class="custom-file-input" onchange="getName()" accept="application/x-bittorrent" required>
                        <span id="torrentName" class="custom-file-control form-control-file">{{ __('messages.torrent.create.choose_torrent_file') }}</span>
                    </label>
                </div>
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">{{ __('messages.torrent.create.torrent_name') }}</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('messages.torrent.create.torrent_name') }}" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="description" class="col-sm-2 col-form-label">{{ __('messages.torrent.create.torrent_description') }}</label>
                    <div class="col-sm-10">
                        <textarea id="description" name="description" class="form-control" placeholder="{{ __('messages.torrent.create.torrent_description') }}" rows="10" required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('messages.torrent.create.upload_button_text') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{asset('js/getName.js')}}"></script>
@endsection