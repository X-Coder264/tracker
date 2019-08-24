@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 mx-auto">
                @include('partials.flash_messages')

                <br>

                <div class="card">
                    <div class="card-header">{{ trans('messages.2fa_auth.caption') }}</div>
                    <div class="card-body">
                        @if ($user->is_two_factor_enabled)
                            <form class="form-horizontal" role="form" method="POST" action="{{ route('2fa.disable') }}">
                        @else
                            <form class="form-horizontal" role="form" method="POST" action="{{ route('2fa.enable') }}">
                        @endif
                            {{ csrf_field() }}
                            <div class="alert alert-warning">
                                {{ trans('messages.2fa.info_message') }}
                            </div>

                            @if (! $user->is_two_factor_enabled)
                                <div class="alert alert-warning">
                                    <a class="btn btn-primary btn-block" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Authenticator - Android</a>
                                    <a class="btn btn-primary btn-block" href="https://apps.apple.com/us/app/google-authenticator/id388497605">Google Authenticator - iOS</a>
                                    <a class="btn btn-primary btn-block" href="https://play.google.com/store/apps/details?id=com.authy.authy">Authy - Android</a>
                                    <a class="btn btn-primary btn-block" href="https://apps.apple.com/us/app/authy/id494168017">Authy - iOS</a>
                                </div>
                            @endif

                            @if (! $user->is_two_factor_enabled)
                                <div class="form-group text-center">
                                    <p>{!! trans('messages.2fa.please_scan_barcode_message') !!}</p>
                                    <p>{!! trans('messages.2fa.use_secret_key_message', ['code' => $user->two_factor_secret_key]) !!}</p>
                                    <img src="{{ $barcode }}" alt="" />
                                </div>
                            @endif

                            <div class="form-group row text-center">
                                <div class="col-md-10 mx-auto">
                                    @if ($user->is_two_factor_enabled)
                                        <button type="submit" class="btn btn-danger">
                                            {{ trans('messages.2fa.disable_button.caption') }}
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success">
                                            {{ trans('messages.2fa.enable_button.caption') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
