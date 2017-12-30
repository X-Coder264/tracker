@extends('layouts.app')

@section('content')
<div class="col-12 mx-auto">
    <div class="card">
        <div class="card-header">{{ __('messages.common.register') }}</div>

        <div class="card-body">
            @include('partials.flash_messages')
            <br>
            <form class="form-horizontal" method="POST" action="{{ route('register') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="name" class="col control-label">{{ __('messages.register.username') }}</label>

                    <div class="col">
                        <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="col control-label">{{ __('messages.common.email') }}</label>

                    <div class="col">
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="col control-label">{{ __('messages.common.password') }}</label>

                    <div class="col">
                        <input id="password" type="password" class="form-control" name="password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password-confirm" class="col control-label">{{ __('messages.common.confirm-password') }}</label>

                    <div class="col">
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="locale" class="col control-label">{{ __('messages.common.language') }}</label>

                    <div class="col">
                        <select class="form-control" name="locale" id="locale" required>
                            @foreach ($locales as $locale)
                                <option value="{{ $locale->id }}">{{ $locale->locale }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @include('partials.timezones_select')

                <div class="form-group">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">
                            {{ __('messages.common.register') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
