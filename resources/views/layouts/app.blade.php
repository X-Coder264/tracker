<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
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
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-sm navbar-light bg-light">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#nav-content" aria-controls="nav-content" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Brand -->
            <a class="navbar-brand" href="{{ route('home.index') }}">
                {{ config('app.name', 'Tracker') }}
            </a>

            <!-- Links -->
            <div class="collapse navbar-collapse" id="nav-content">
                <ul class="navbar-nav w-100">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item nav-item">
                            <a class="nav-item nav-link" href="{{ route('login') }}">{{ __('messages.common.login') }}</a>
                        </li>
                        <li class="nav-item nav-item">
                            <a class="nav-item nav-link" href="{{ route('register') }}">{{ __('messages.common.register') }}</a>
                        </li>
                    @else
                            <li class="nav-item">
                                <a class="nav-item nav-link" href="{{ route('torrents.index') }}">{{ __('messages.navigation.torrent.index') }}</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-item nav-link" href="{{ route('torrents.create') }}">{{ __('messages.navigation.torrent.create') }}</a>
                            </li>

                            <li class="dropdown show nav-item ml-auto">
                                <a class="nav-item nav-link dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                                    <a class="dropdown-item" href="{{ route('users.edit', Auth::user()) }}">{{ __('messages.navigation.users.edit_page') }}</a>
                                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">{{ __('messages.navigation.logout') }}</a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </div>
                            </li>
                    @endguest
                </ul>
            </div>
        </nav>
        <br>

        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    @yield('scripts')

</body>
</html>
