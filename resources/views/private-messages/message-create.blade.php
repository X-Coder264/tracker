@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrent-comments.create.header', ['torrent' => $thread->subject]) }}
            </div>

            <div class="card-body">
                @include('partials.flash_messages')
                <br>
                <form method="POST" action="{{ route('thread-messages.store', $thread) }}">
                    {{ csrf_field() }}

                    @include('private-messages.message-form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
