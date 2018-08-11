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
        <nav class="navbar navbar-expand-sm navbar-light bg-light">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nav-content" aria-controls="nav-content" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand" href="{{ route('home') }}">
                {{ config('app.name', 'Tracker') }}
            </a>

            <div class="collapse navbar-collapse" id="nav-content">
                <ul class="navbar-nav w-100">
                    @guest
                        <li class="nav-item">
                            <a class="nav-item nav-link" href="{{ route('login') }}">{{ trans('messages.common.login') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ trans('messages.common.register') }}</a>
                        </li>
                    @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('torrents.index') }}">{{ trans('messages.navigation.torrent.index') }}</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('torrents.create') }}">{{ trans('messages.navigation.torrent.create') }}</a>
                            </li>

                            <li class="dropdown show nav-item ml-auto">
                                <a class="nav-link dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ auth()->user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                                    <a class="dropdown-item" href="{{ route('users.edit', auth()->user()) }}">{{ trans('messages.navigation.users.edit_page') }}</a>
                                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">{{ trans('messages.navigation.logout') }}</a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </div>
                            </li>
                    @endguest
                </ul>
            </div>
        </nav>

        <main role="main" class="py-4">
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
