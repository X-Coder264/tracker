@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            @include('partials.flash_messages')
            <br>

            <div class="card">
                @if ($invitees->isEmpty())
                    <div class="card-body">
                        <p class="card-text">{{ trans('messages.invites.no_invitees') }}</p>
                    </div>
                @else
                    <div class="card-header">
                        {{ trans('messages.invites.invited_users') }}
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-lg">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ trans('messages.invites.user_name') }}</th>
                                        <th>{{ trans('messages.invites.user_created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invitees as $invitee)
                                        <tr>
                                            <td><a href="{{ route('users.show', $invitee) }}">{{ $invitee->name }}</a></td>
                                            <td>
                                                {{ $invitee->created_at->timezone($timezone) }}
                                                ({{ $invitee->created_at->diffForHumans() }})
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <br>

    @if ($invites->isNotEmpty())
        <div class="row justify-content-center">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        {{ trans('messages.invites.valid_invites') }}
                    </div>

                    <div class="card-body">
                        <div class="table-responsive-lg">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ trans('messages.invite_code_label_caption') }}</th>
                                        <th>{{ trans('messages.invites.valid_until') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invites as $invite)
                                        <tr>
                                            <td>{{ $invite->code }}</td>
                                            <td>
                                                {{ $invite->expires_at->timezone($timezone) }}
                                                ({{ $invite->expires_at->diffForHumans() }})
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
    @endif

    @if (0 !== $user->invites_amount)
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-body">
                        <br>
                        <form method="POST" action="{{ route('invites.store') }}">
                            {{ csrf_field() }}

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">{{ trans('messages.invites.create') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card text-dark bg-warning">
                    <div class="card-body">
                        <p class="card-text">{{ trans('messages.invites.no_invites_left') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
