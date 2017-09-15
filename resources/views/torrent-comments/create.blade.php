@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">
            {{ trans('messages.torrent-comments.create.header', ['torrent' => $torrent->name]) }}
        </div>

        <div class="card-body">
            @include('partials.flash_messages')
            <br>
            <form method="POST" action="{{ route('torrent-comments.store', $torrent) }}">
                {{ csrf_field() }}

                @include('torrent-comments.form')
            </form>
        </div>
    </div>
</div>
@endsection
