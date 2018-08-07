@extends('layouts.app')

@section('content')
@include('partials.flash_messages')
<div class="col-12 col-lg-10 col-xl-10 mx-auto">
    <div class="card">
        <div class="card-header">{{ trans('messages.home.index.page_title') }}</div>
        <div class="card-body">
            {{ trans('messages.home.index.no_news') }}
        </div>
    </div>
</div>
@endsection
