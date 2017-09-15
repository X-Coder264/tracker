@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ trans('messages.users.edit.page_title') }}
                </div>

                <div class="card-body">
                    @include('partials.flash_messages')
                    <br>
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        {{ method_field('PUT') }}
                        {{ csrf_field() }}

                        <div class="form-group row">
                            <label for="email" class="col-sm-4 col-form-label text-md-right">{{ trans('messages.common.email') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="locale" class="col-sm-4 col-form-label text-md-right">{{ trans('messages.common.language') }}</label>

                            <div class="col-md-6">
                                <select class="form-control" name="locale_id" id="locale" required>
                                    @foreach ($locales as $locale)
                                        <option value="{{ $locale->id }}" @if($locale->id === $user->locale_id) selected @endif>{{ $locale->locale }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @include('partials.timezones_select')

                        <div class="form-group row">
                            <label for="torrents_per_page" class="col-sm-4 col-form-label text-md-right">{{ trans('messages.user.edit-torrents-per-page') }}</label>

                            <div class="col-md-6">
                                <input id="torrents_per_page" type="number" class="form-control" name="torrents_per_page" value="{{ old('torrents_per_page', $user->torrents_per_page) }}" required>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">{{ trans('messages.common.save_changes') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
