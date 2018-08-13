@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrent.create.page_title') }}
            </div>

            <div class="card-body">
                @include('partials.flash_messages')
                <br>
                <form enctype="multipart/form-data" method="POST" action="{{ route('torrents.store') }}">
                    {{ csrf_field() }}

                    <div class="form-group row">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input{{ $errors->has('torrent') ? ' is-invalid' : '' }}" id="torrent" name="torrent" onchange="getName()" accept="application/x-bittorrent" required>
                            <label id="torrent-file-label" class="custom-file-label" for="torrent">{{ trans('messages.torrent.create.choose_torrent_file') }}</label>

                            @if ($errors->has('torrent'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('torrent') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="name" class="col-sm-2 col-form-label">{{ trans('messages.torrent.create.torrent_name') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" id="name" name="name" placeholder="{{ trans('messages.torrent.create.torrent_name') }}" value="{{ old('name') }}" required>

                            @if ($errors->has('name'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="imdb_url" class="col-sm-2 col-form-label">{{ trans('messages.torrent.IMDB-URL') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control{{ $errors->has('imdb_url') ? ' is-invalid' : '' }}" id="imdb_url" name="imdb_url" placeholder="{{ trans('messages.torrent.IMDB-URL') }}" value="{{ old('imdb_url') }}" required>

                            @if ($errors->has('imdb_url'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('imdb_url') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="description" class="col-sm-2 col-form-label">{{ trans('messages.torrent.create.torrent_description') }}</label>
                        <div class="col-sm-10">
                            <textarea id="description" name="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" placeholder="{{ trans('messages.torrent.create.torrent_description') }}" rows="10" required>{{ old('description') }}</textarea>

                            @if ($errors->has('description'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('description') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="category" class="col-sm-2 col-form-label">{{ trans('messages.torrent.create.torrent_category') }}</label>

                        <div class="col-sm-10">
                            <select class="form-control" name="category" id="category" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-md-8 offset-md-4">
                            <button type="submit" class="btn btn-primary">{{ trans('messages.torrent.create.upload_button_text') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/getName.js') }}"></script>
@endsection
