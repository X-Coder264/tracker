@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.common.login') }}
            </div>

            <div class="card-body">

                @if (session('error'))
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h4 class="card-title">{{ trans('messages.common.error') }}</h4>
                            <p class="card-text">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    {{ csrf_field() }}

                    <div class="form-group row">
                        <label for="email" class="col-sm-4 col-form-label text-md-right">{{ trans('messages.common.email') }}</label>

                        <div class="col-md-6">
                            <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>

                            @if ($errors->has('email'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="password" class="col-md-4 col-form-label text-md-right">{{ trans('messages.common.password') }}</label>

                        <div class="col-md-6">
                            <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                            @if ($errors->has('password'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 offset-md-4">
                              <div class="custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0">
                                   <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                   <label class="custom-control-label" for="remember">{{ trans('messages.login.remember-me') }}</label>
                              </div>
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-md-8 offset-md-4">
                            <button type="submit" class="btn btn-primary">
                                {{ trans('messages.common.login') }}
                            </button>
                            <br>
                            <a class="btn btn-link" href="{{ route('register') }}">
                                {{ trans('messages.login.register-link-text') }}
                            </a>
                            <br>
                            <a class="btn btn-link" href="{{ route('password.request') }}">
                                {{ trans('messages.login.reset-password-link-text') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
