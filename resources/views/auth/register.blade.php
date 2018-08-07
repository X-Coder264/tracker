@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">{{ trans('messages.common.register') }}</div>

        <div class="card-body">
            <form class="form-horizontal" method="POST" action="{{ route('register') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="name" class="col control-label">{{ trans('messages.register.username') }}</label>

                    <div class="col">
                        <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required>

                        @if ($errors->has('name'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('name') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="col control-label">{{ trans('messages.common.email') }}</label>

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
                    <label for="password" class="col control-label">{{ trans('messages.common.password') }}</label>

                    <div class="col">
                        <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                        @if ($errors->has('password'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('password') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label for="password-confirm" class="col control-label">{{ trans('messages.common.confirm-password') }}</label>

                    <div class="col">
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="locale" class="col control-label">{{ trans('messages.common.language') }}</label>

                    <div class="col">
                        <select class="form-control{{ $errors->has('locale') ? ' is-invalid' : '' }}" name="locale" id="locale" required>
                            @foreach ($locales as $locale)
                                <option value="{{ $locale->id }}">{{ $locale->locale }}</option>
                            @endforeach
                        </select>

                        @if ($errors->has('locale'))
                            <div class="invalid-feedback">
                                <strong>{{ $errors->first('locale') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                @include('partials.timezones_select')

                <div class="form-group">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">
                            {{ trans('messages.common.register') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
