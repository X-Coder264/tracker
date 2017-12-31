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
                    <div class="custom-file">
                        <input type="file" class="custom-file-input{{ $errors->has('torrent') ? ' is-invalid' : '' }}" id="torrent" name="torrent" onchange="getName()" accept="application/x-bittorrent" required>
                        <label id="torrent-file-label" class="custom-file-label" for="torrent">{{ __('messages.torrent.create.choose_torrent_file') }}</label>

                        @if ($errors->has('torrent'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('torrent') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">{{ __('messages.torrent.create.torrent_name') }}</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" id="name" name="name" placeholder="{{ __('messages.torrent.create.torrent_name') }}" value="{{ old('name') }}" required>

                        @if ($errors->has('name'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('name') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-group row">
                    <label for="description" class="col-sm-2 col-form-label">{{ __('messages.torrent.create.torrent_description') }}</label>
                    <div class="col-sm-10">
                        <textarea id="description" name="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" placeholder="{{ __('messages.torrent.create.torrent_description') }}" rows="10" required>{{ old('description') }}</textarea>

                        @if ($errors->has('description'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('description') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('messages.torrent.create.upload_button_text') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/getName.js') }}"></script>
@endsection