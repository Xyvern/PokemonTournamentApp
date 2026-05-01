<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dashboard')</title>
    
    {{-- 1. CSS FILES --}}
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
</head>

<body class="d-flex flex-column min-vh-100">

    {{-- Navbar --}}
    @if (!Auth::user() || Auth::user()->role == 1)
        @include('player.navbar')
    @else
        @include('admin.navbar')
    @endif
    @if(!auth()->check() || !auth()->user()->isPremium())
        <div class="ad-container text-center bg-white p-3 rounded shadow-sm border mb-4" style="position: relative; overflow: hidden;">
            
            {{-- Faint label at the top --}}
            <small class="text-muted d-block mb-2 font-weight-bold" style="letter-spacing: 1px; font-size: 0.7rem;">SPONSORED</small>

            {{-- LOCALHOST / ADBLOCK FALLBACK (Sits behind the ad) --}}
            <div class="ad-fallback d-flex flex-column align-items-center justify-content-center w-100 position-absolute" style="top: 0; left: 0; height: 100%; z-index: 0; opacity: 0.1; pointer-events: none;">
                <i class="fas fa-ad fa-4x mb-2"></i>
                <span class="font-weight-bold">Ad Space</span>
            </div>

            {{-- THE ACTUAL GOOGLE AD (Sits on top) --}}
            <div style="position: relative; z-index: 1; height: 100px;">
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                    style="display:block; height:100px;" 
                    data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
                    data-ad-slot="XXXXXXXXXX"
                    data-ad-format="horizontal" 
                    data-full-width-responsive="true"></ins>
                <script>
                    (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>
        </div>
    @endif
    {{-- Main Content --}}
    <div class="flex-grow-1">
        @yield('content')
    </div>

    {{-- Footer --}}
    <footer class="bg-white text-center py-4 border-top mt-5">
        <div class="container">
            <p class="mb-0 text-muted">
                &copy; {{ date('Y') }} Copyright Reserved by Pokémon.
            </p>
            <small class="text-muted" style="font-size: 0.75rem;">
                This website is created for educational purpose only. The literal and graphical information presented on this website about the Pokémon Trading Card Game, including card images and text, is copyright The Pokémon Company (Pokémon), Nintendo, Game Freak and/or Creatures. This website is not produced by, endorsed by, supported by, or affiliated with Pokémon, Nintendo, Game Freak or Creatures.
            </small>
        </div>
    </footer>

    
    {{-- 1. jQuery --}}
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    {{-- 2. Bootstrap --}}
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    {{-- 3. DataTables Core --}}
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    {{-- 4. DataTables BS4 --}}
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    {{-- 5. AdminLTE --}}
    <script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>

    @include('partials.toasts')

    {{-- ADD THIS LINE HERE: --}}
    @stack('scripts')

</body>
</html>