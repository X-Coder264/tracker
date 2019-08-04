@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.torrent.create.page_title') }}
            </div>

            <div class="card-body">
                @include('partials.flash_messages')
                <br>
                @include('torrents.form', $torrent)
            </div>
        </div>
    </div>
</div>
@endsection
