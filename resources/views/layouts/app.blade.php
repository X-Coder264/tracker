<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Tracker') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fontawesome.min.css') }}" rel="stylesheet">

    @yield('stylesheets')

</head>
<body style="height: 100vh;" class="d-flex flex-column">
    <div class="container-fluid" style="flex: 1 0 auto;">

        @include('partials.navigation')

        <main role="main" class="py-4">
            @include('partials.user-statistics')

            @yield('content')
        </main>
    </div>

    @include('partials.footer')

    <!-- Scripts -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/fontawesome.min.js') }}"></script>

    @yield('scripts')

</body>
</html>
