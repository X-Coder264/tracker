@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">
            {{ __('messages.torrent-comments.edit.header') }}
        </div>

        <div class="card-body">
            @include('partials.flash_messages')
            <br>
            <form method="POST" action="{{ route('torrent-comments.update', $torrentComment) }}">
                {{ csrf_field() }}
                {{ method_field('PUT') }}

                @include('torrent-comments.form')
            </form>
        </div>
    </div>
</div>
@endsection
