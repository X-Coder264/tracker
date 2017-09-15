@extends('layouts.app')

@section('content')
<div class="col-10 mx-auto">
    <div class="card">
        <div class="card-header">{{ __('messages.home.index.page_title') }}</div>

        <div class="card-body">
            @include('partials.flash_messages')
            {{ __('messages.home.index.no_news') }}
        </div>
    </div>
</div>
@endsection
