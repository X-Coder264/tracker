@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">
            {{ __('messages.torrent-comments.show.header', ['torrent' => $torrent->name]) }}
        </div>

        <div class="card-body">
            @include('partials.flash_messages')
            <br>
            <form method="POST" action="{{ route('torrent-comments.store', $torrent) }}">
                {{ csrf_field() }}

                <div class="form-group row">
                    <div class="col">
                        <textarea id="comment" name="comment" class="form-control{{ $errors->has('comment') ? ' is-invalid' : '' }}" rows="10" required>{{ old('comment') }}</textarea>

                        @if ($errors->has('comment'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('comment') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('messages.common.submit') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection