@extends('layouts.app')

@section('content')
@include('partials.flash_messages')
<div class="col-12 col-lg-10 col-xl-10 mx-auto">
    <div class="card">
        <div class="card-header">{{ __('messages.home.index.page_title') }}</div>
        <div class="card-body">
            {{ __('messages.home.index.no_news') }}
        </div>
    </div>
</div>
@endsection
