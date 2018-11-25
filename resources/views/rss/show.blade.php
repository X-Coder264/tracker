@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                {{ trans('messages.rss.feed') }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.rss.store') }}">
                    {{ csrf_field() }}

                    <div class="form-row align-items-center">
                        <div class="col-auto my-1">
                            @foreach ($categories as $category)
                                <div class="custom-control custom-checkbox">
                                    <input id="{{ 'category.' . $category->id }}" type="checkbox" class="custom-control-input" name="categories[]" value="{{ $category->id }}">
                                    <label for="{{ 'category.' . $category->id }}" class="custom-control-label">{{ $category->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if (session('rssURL'))
                        <div class="form-group row">
                            <label for="link" class="col-sm-4 col-form-label text-md-right">{{ trans('messages.rss.link') }}</label>

                            <div class="col-md-8">
                                <input id="link" type="text" class="form-control" name="link" value="{{ session('rssURL') }}" readonly>
                            </div>
                        </div>
                        <br>
                    @endif

                    <div class="form-group row mb-0">
                        <div class="col-md-8 offset-md-4">
                            <button type="submit" class="btn btn-primary">{{ trans('messages.rss.get-link') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
