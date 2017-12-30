@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">{{ __('messages.common.login') }}</div>

        <div class="card-body">
            <form class="form-horizontal" method="POST" action="{{ route('login') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="email" class="col control-label">{{ __('messages.common.email') }}</label>

                    <div class="col">
                        <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>

                        @if ($errors->has('email'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('email') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="col control-label">{{ __('messages.common.password') }}</label>

                    <div class="col">
                        <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                        @if ($errors->has('password'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('password') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col">
                          <div class="custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0">
                               <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                               <label class="custom-control-label" for="remember">{{ __('messages.login.remember-me') }}</label>
                          </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">
                            {{ __('messages.common.login') }}
                        </button>
                        <br>
                        <a class="btn btn-link" href="{{ route('register') }}">
                            {{ __('messages.login.register-link-text') }}
                        </a>
                        <br>
                        <a class="btn btn-link" href="{{ route('password.request') }}">
                            {{ __('messages.login.reset-password-link-text') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
