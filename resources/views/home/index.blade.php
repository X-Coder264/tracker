@extends('layouts.app')

@section('content')
@include('partials.flash_messages')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ trans('messages.home.page_title') }}</div>
                <div class="card-body">
                    {{ trans('messages.home.no_news') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
