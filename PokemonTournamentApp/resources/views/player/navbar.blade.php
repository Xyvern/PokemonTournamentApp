<nav class="navbar navbar-expand navbar-primary navbar-dark" style="background-color: #434343;">
    <ul class="navbar-nav">
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('player.home') }}" class="nav-link">
                <img src="{{ asset('assets/pcg_logo.png') }}" alt="" style="height: 6vh;">
            </a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a href="#" class="nav-link">
                Tournaments
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('player.mydecks') }}" class="nav-link">
                Decks
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('player.leaderboard') }}" class="nav-link">
                Leaderboard
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('player.profile', Auth::user()->id) }}" class="nav-link">
                Profile
            </a>
        </li>
    </ul>
</nav>