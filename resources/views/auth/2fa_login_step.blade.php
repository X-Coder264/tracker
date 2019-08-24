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

                <form method="POST" action="{{ route('2fa.verify') }}">
                    {{ csrf_field() }}

                    <div class="form-group row">
                        <label for="code" class="col-sm-4 col-form-label text-md-right">{{ trans('messages.2fa_code.caption') }}</label>

                        <div class="col-md-6">
                            <input id="code" type="text" class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}" name="code" value="{{ old('code') }}" required autofocus>

                            @if ($errors->has('code'))
                                <div class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('code') }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-md-8 offset-md-4">
                            <button type="submit" class="btn btn-primary">
                                {{ trans('messages.common.login') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
