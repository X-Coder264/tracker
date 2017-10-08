@extends('layouts.app')

@section('content')
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ __('messages.users.edit.page_title') }}
            </div>

            <div class="card-body">
                @include('partials.flash_messages')
                <br>
                <form method="POST" action="{{ route('users.update', $user) }}">
                    {{ method_field('PUT') }}
                    {{ csrf_field() }}

                    <div class="form-group">
                        <label for="email" class="col control-label">{{ __('messages.common.email') }}</label>

                        <div class="col">
                            <input id="email" type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="locale" class="col control-label">{{ __('messages.common.language') }}</label>

                        <div class="col">
                            <select class="form-control" name="locale_id" id="locale" required>
                                @foreach($locales as $locale)
                                    <option value="{{ $locale->id }}" @if($locale->id === $user->locale_id) selected @endif>{{ $locale->locale }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @include('partials.timezones_select')

                    <button type="submit" class="btn btn-primary">{{ __('messages.common.save_changes') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection