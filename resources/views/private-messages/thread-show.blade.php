@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-10 mx-auto">
        @include('partials.flash_messages')
        <div class="card">
            <div class="card-header">
                {{ $thread->subject }} - {!! $participantsText !!}
            </div>

            <div class="card-body">
                <a href="{{ route('thread-messages.create', $thread) }}" class="btn btn-primary btn-block">
                    {{ trans('messages.torrents.show.add_comment') }}
                </a>
                <br>
                @foreach ($messages as $message)
                    <div class="card">
                        <div class="card-header">
                            <a href="{{ route('users.show', $message->user) }}">{{ $message->user->name }}</a> - {{ $message->created_at->timezone($timezone) }} ({{ $message->created_at->diffForHumans() }})
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-2 d-none d-sm-block">
                                    <img src="https://i.imgur.com/eJhRU6c.jpg" class="img-fluid" alt="">
                                </div>
                                <div class="col-10">
                                    <p class="card-text">{{ $message->message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                @endforeach
                <div class="row justify-content-center">
                    {{ $messages->render() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
