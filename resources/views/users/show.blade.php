@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive-lg">
                    <table class="table table-hover table-bordered">
                        <tbody>
                            <tr>
                                <th>{{ trans('messages.user.profile_last_seen_at') }}</th>
                                <td>
                                    @if (null === $user->last_seen_at)
                                        {{ trans('messages.user.profile_last_seen_at_never') }}
                                    @else
                                        {{ $user->last_seen_at->timezone($timezone)->format('d.m.Y. H:i') }} ({{ $user->last_seen_at->diffForHumans() }})
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.user.profile_registered_at') }}</th>
                                <td>{{ $user->created_at->timezone($timezone)->format('d.m.Y. H:i') }} ({{ $user->created_at->diffForHumans() }})</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.uploaded') }}</th>
                                <td>{{ $user->uploaded }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('messages.common.downloaded') }}</th>
                                <td>{{ $user->downloaded }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
