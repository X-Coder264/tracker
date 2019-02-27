@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('test') }} - <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
            </div>

            <div class="card-body">
                @if ($threads->isEmpty())
                    <div class="card text-dark bg-warning">
                        <div class="card-body">
                            <h4 class="card-title">{{ trans('messages.common.notice') }}</h4>
                            <p class="card-text">{{ trans('messages.torrent.user.page_no_torrents') }}</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive-lg">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ trans('messages.common.category') }}</th>
                                    <th>{{ trans('messages.common.category') }}</th>
                                    <th>{{ trans('messages.common.category') }}</th>
                                    <th>{{ trans('messages.common.category') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($threads as $thread)
                                    <tr @if ($unreadThreads->contains($thread->id)) class="table-danger" @endif>
                                        <td>
                                            <a href="{{ route('threads.show', $thread) }}">{{ $thread->subject }}</a>
                                        </td>
                                        <td>
                                            <a href="{{ route('users.show', $thread->creator) }}">{{ $thread->creator->name }}</a>
                                        </td>
                                        <td>
                                            {{ $thread->created_at->timezone($timezone) }}
                                            {{ $thread->created_at->diffForHumans() }}
                                        </td>
                                        <td>
                                            {{ $thread->updated_at->timezone($timezone) }}
                                            {{ $thread->updated_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="row justify-content-center">
                        {{ $threads->render() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
