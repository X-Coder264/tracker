<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Tracker') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    @yield('stylesheets')

</head>
<body style="height: 100vh;" class="d-flex flex-column">
    <div class="container-fluid" style="flex: 1 0 auto;">

        @include('partials.navigation')

        <main role="main" class="py-4">

            @if (auth()->check())
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <div class="card">
                            <div class="card-body">
                                {{ trans('messages.common.uploaded') }}: {{ auth()->user()->uploaded }} |
                                {{ trans('messages.common.downloaded') }}: {{ auth()->user()->downloaded }} |
                                {{ trans('messages.common.seeding') }}: {{ $numberOfSeedingTorrents }} |
                                {{ trans('messages.common.leeching') }}: {{ $numberOfLeechingTorrents }}
                            </div>
                        </div>
                    </div>
                </div>

                <br>
            @endif

            @yield('content')
        </main>
    </div>

    <footer class="footer" style="flex-shrink: 0;">
        <div class="container-fluid">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted text-center">LaraTracker {{ date('Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    @yield('scripts')

</body>
</html>
