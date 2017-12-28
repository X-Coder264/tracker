@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">
            Test {{ $torrent->name }}
            {{ __('messages.torrent.create.page_title') }}
        </div>

        <div class="card-body">
            @include('partials.flash_messages')
            <br>
            <form method="POST" action="{{ route('torrent-comments.store', $torrent) }}">
                {{ csrf_field() }}

                <div class="form-group row">
                    <div class="col">
                        <textarea id="comment" name="comment" class="form-control" rows="10" required>{{ old('comment') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('messages.torrent.create.upload_button_text') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection