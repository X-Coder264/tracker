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
                    <a class="nav-item nav-link" href="{{ route('login') }}">
                        {{ trans('messages.common.login') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('register') }}">
                        {{ trans('messages.common.register') }}
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('torrents.index') }}">
                        {{ trans('messages.navigation.torrent.index') }}
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('torrents.create') }}">
                        {{ trans('messages.navigation.torrent.create') }}
                    </a>
                </li>

                <li class="dropdown show nav-item ml-auto">
                    <a class="nav-link dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ auth()->user()->name }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                        <a class="dropdown-item" href="{{ route('users.edit', auth()->user()) }}">
                            {{ trans('messages.navigation.users.edit_page') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('2fa.status') }}">
                            {{ trans('messages.2fa_auth.caption') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('users.show', auth()->user()) }}">
                            {{ trans('messages.navigation.users.profile') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('user-torrents.show-uploaded-torrents', auth()->user()) }}">
                            {{ trans('messages.navigation.my-torrents') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('users.rss.show') }}">
                            {{ trans('messages.navigation.rss') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('invites.create') }}">
                            {{ trans('messages.navigation.invites') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                            {{ trans('messages.navigation.logout') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                        </form>
                    </div>
                </li>
            @endguest
        </ul>
    </div>
</nav>
