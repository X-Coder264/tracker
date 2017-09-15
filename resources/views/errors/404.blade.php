@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.common.error') }}
            </div>
            <div class="card-body bg-danger">
                <p class="card-text">{{ $exception->getMessage() ?? trans('messages.404.message') }}</p>
                <a href="{{ url()->previous() }}" class="btn btn-primary">{{ trans('messages.404.go_back_message') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
