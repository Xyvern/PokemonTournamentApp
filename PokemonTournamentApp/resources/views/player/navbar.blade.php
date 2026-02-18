<style>
    /* Custom Navbar Styling */
    .navbar-custom {
        background-color: #434343;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    
    .navbar-brand img {
        transition: transform 0.2s;
    }
    
    .navbar-brand:hover img {
        transform: scale(1.05);
    }

    .nav-link {
        font-weight: 500;
        transition: color 0.2s ease-in-out;
        margin-right: 10px;
    }

    /* Active State (Underline effect) */
    .nav-item.active .nav-link {
        color: #fff !important;
        font-weight: 700;
        border-bottom: 2px solid #007bff; /* AdminLTE Blue or your theme color */
    }

    /* Hover State */
    .nav-link:hover {
        color: #e0e0e0 !important;
    }

    /* Dropdown Polish */
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-radius: 8px;
        margin-top: 10px;
    }
    
    .dropdown-item {
        padding: 10px 20px;
    }
    
    .dropdown-item:active {
        background-color: #434343;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm" style="background-color: #434343 !important;">
    <div class="container-fluid px-4 px-lg-5">
        {{-- 1. LOGO (Left Side) --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ route('player.home') }}">
            <img src="{{ asset('assets/pcg_logo.png') }}" alt="Logo" style="height: 50px;" class="mr-2">
        </a>

        {{-- Mobile Toggler --}}
        <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- 2. LINKS GROUP (Right Side) --}}
        <div class="collapse navbar-collapse" id="navbarContent">
            {{-- "ml-auto" pushes this entire ul to the right --}}
            <ul class="navbar-nav ml-auto align-items-center">
                
                {{-- Navigation Links --}}
                <li class="nav-item">
                    <a href="{{ route('tournaments.index') }}" class="nav-link px-3 {{ request()->routeIs('tournaments.*') ? 'active font-weight-bold text-white' : '' }}">
                        Tournaments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('archetypes.index') }}" class="nav-link px-3 {{ request()->routeIs('archetypes.*') ? 'active font-weight-bold text-white' : '' }}">
                        Archetypes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('cards.index') }}" class="nav-link px-3 {{ request()->routeIs('cards.*') ? 'active font-weight-bold text-white' : '' }}">
                        Cards
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('player.leaderboard') }}" class="nav-link px-3 {{ request()->routeIs('player.leaderboard') ? 'active font-weight-bold text-white' : '' }}">
                        Leaderboard
                    </a>
                </li>

                {{-- Divider for Desktop --}}
                <li class="nav-item d-none d-lg-block mx-2 text-secondary">|</li>

                {{-- User Auth Section --}}
                @if (Auth::user())
                    <li class="nav-item dropdown ml-lg-2">
                        <a id="userDropdown" class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="bg-light text-dark rounded-circle d-flex justify-content-center align-items-center mr-2" style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span>{{ Auth::user()->nickname }}</span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right border-0 shadow" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="{{ route('player.profile', Auth::user()->id) }}">
                                <i class="fas fa-id-card mr-2 text-muted"></i> Profile
                            </a>
                            <a class="dropdown-item" href="{{ route('player.mydecks') }}">
                                <i class="fas fa-layer-group mr-2 text-muted"></i> My Decks
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @else
                    <li class="nav-item ml-lg-2">
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm px-4 rounded-pill">
                            Login
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>