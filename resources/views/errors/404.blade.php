@extends('layouts.app')

@section('content')
    <div class="col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.common.error') }}
            </div>
            <div class="card-body bg-danger">
                <p class="card-text">{{ $exception->getMessage() }}</p>
                <a href="{{ url()->previous() }}" class="btn btn-primary">{{ trans('messages.404.go_back_message') }}</a>
            </div>
        </div>
    </div>
@endsection
