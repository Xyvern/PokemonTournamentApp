@extends('admin.layout')

@section('title', 'Manage Players')

@section('content')

<style>
    /* CSS Grid for exactly 5 columns */
    .grid-5-cols {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.5rem;
    }
    
    /* Responsive breakpoints */
    @media (max-width: 1400px) { .grid-5-cols { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 1100px) { .grid-5-cols { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .grid-5-cols { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .grid-5-cols { grid-template-columns: 1fr; } }

    .player-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        padding: 1.5rem 1rem;
        text-align: center;
        border: 1px solid #e9ecef;
        transition: transform 0.2s;
    }
    .player-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    .deactivated-card {
        background-color: #f8f9fa;
        opacity: 0.85;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">

    {{-- 1. PAGE HEADER --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">Manage Players</h2>
                            <p class="text-muted mb-0">View stats and manage account access for all registered competitors.</p>
                        </div>
                        
                        {{-- Optional: You can add a search bar or filter buttons here later, just like the tournaments page! --}}
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <div class="badge badge-light border px-3 py-2 text-muted shadow-sm">
                                <i class="fas fa-users mr-1"></i> Total Players: {{ $players->total() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4">{{ session('success') }}</div>
    @endif

    {{-- 2. PLAYER GRID CONTENT --}}
    <div class="row">
        <div class="col-12">
            <div class="grid-5-cols mb-4">
                @forelse($players as $player)
                    @php
                        // Prevent division by zero
                        $winRate = $player->matches_played > 0 
                            ? round(($player->matches_won / $player->matches_played) * 100) 
                            : 0;
                    @endphp
                    
                    <div class="player-card {{ $player->trashed() ? 'deactivated-card' : '' }}">
                        
                        {{-- Status Badge --}}
                        <div class="mb-2 text-right">
                            @if($player->trashed())
                                <span class="badge badge-danger px-2 py-1">Inactive</span>
                            @else
                                <span class="badge badge-success px-2 py-1">Active</span>
                            @endif
                        </div>

                        {{-- Player Name & Info --}}
                        <h5 class="font-weight-bold text-dark mb-0 text-truncate" title="{{ $player->nickname }}">
                            {{ $player->nickname ?? $player->name }}
                        </h5>
                        <small class="text-muted d-block mb-3">{{ $player->username }}</small>

                        {{-- ELO Display --}}
                        <div class="d-inline-block bg-light px-3 py-1 rounded-pill border mb-3">
                            <span class="h5 font-weight-bold text-primary mb-0">{{ $player->elo }}</span>
                            <small class="text-muted text-uppercase font-weight-bold ml-1">ELO</small>
                        </div>

                        {{-- Mini Stats --}}
                        <div class="row text-center mb-3 px-2">
                            <div class="col-6 border-right">
                                <span class="d-block font-weight-bold text-dark">{{ $player->matches_played }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">MATCHES</small>
                            </div>
                            <div class="col-6">
                                <span class="d-block font-weight-bold text-dark">{{ $winRate }}%</span>
                                <small class="text-muted" style="font-size: 0.7rem;">WINRATE</small>
                            </div>
                        </div>

                        <hr class="mt-0 mb-3">

                        {{-- Toggle Button --}}
                        <form action="{{ route('admin.players.toggle', $player->id) }}" method="POST">
                            @csrf
                            @if($player->trashed())
                                <button type="submit" class="btn btn-outline-success btn-sm btn-block font-weight-bold">
                                    <i class="fas fa-user-check mr-1"></i> Activate
                                </button>
                            @else
                                <button type="submit" class="btn btn-outline-danger btn-sm btn-block font-weight-bold" onclick="return confirm('Are you sure you want to deactivate this player? They will not be able to log in.')">
                                    <i class="fas fa-user-slash mr-1"></i> Deactivate
                                </button>
                            @endif
                        </form>

                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No players found.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 3. PAGINATION --}}
    @if($players->hasPages())
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-center mb-5">
                    {{ $players->links() }}
                </div>
            </div>
        </div>
    @endif

</div>
@endsection