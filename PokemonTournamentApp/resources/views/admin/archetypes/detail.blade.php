@extends('admin.layout')

@section('title', $archetype->name . ' Detail')

@section('content')

<style>
    /* Custom Table Styling to match reference */
    .table-clean {
        width: 100%;
        background-color: white;
        border-collapse: collapse; 
    }
    .table-clean thead th {
        border-bottom: 2px solid #dee2e6;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
    }
    .table-clean tbody tr {
        border-bottom: 1px solid #f2f2f2;
    }
    .table-clean tbody tr:hover {
        background-color: #f8f9fa;
    }
    .table-clean td {
        padding: 12px;
        vertical-align: middle;
        color: #555;
    }
    .place-cell {
        font-weight: bold;
        color: #0056b3;
    }
    .deck-list-icon {
        color: #333;
        font-size: 1.2rem;
        transition: color 0.2s;
    }
    .deck-list-icon:hover {
        color: #0056b3;
    }
    .section-title {
        border-left: 4px solid #333;
        padding-left: 10px;
        margin-bottom: 20px;
        margin-top: 40px;
        font-weight: bold;
        color: #333;
    }
</style>

<div style="margin-left: 10vw; margin-top: 2vh; margin-right: 10vw;">
    
    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('admin.archetypes.index') }}" class="text-decoration-none text-muted font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Back to Archetypes
        </a>
    </div>

    {{-- 1. HEADER SECTION --}}
    <div class="text-center mb-5">
        <div class="mb-3 d-inline-block shadow-sm rounded overflow-hidden" style="width: 200px;">
            @php
                $image = 'https://asia.pokemon-card.com/id/card-img/products/Back%20of%20card.png';
                if($archetype->keyCard && $archetype->keyCard->images) {
                    $image = $archetype->keyCard->images->large ?? $archetype->keyCard->images->small;
                }
            @endphp
            <img src="{{ $image }}" alt="{{ $archetype->name }}" class="img-fluid">
        </div>

        <h1 class="font-weight-bold display-4">{{ $archetype->name }}</h1>

        <div class="d-flex justify-content-center gap-4 mt-3">
            <div class="px-4 py-2 bg-white rounded shadow-sm border mx-2">
                <span class="d-block text-muted small text-uppercase">Total Games</span>
                <span class="h4 font-weight-bold text-primary">{{ $archetype->times_played }}</span>
            </div>
            <div class="px-4 py-2 bg-white rounded shadow-sm border mx-2">
                <span class="d-block text-muted small text-uppercase">Global Win Rate</span>
                <span class="h4 font-weight-bold text-success">{{ $archetype->win_rate }}%</span>
            </div>
        </div>
    </div>

    {{-- 2. LATEST RESULTS TABLE --}}
    <h4 class="section-title">Latest Results</h4>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th width="15%">Date</th>
                        <th width="10%">Place</th>
                        <th width="30%">Tournament</th>
                        <th width="20%">Player</th>
                        <th width="10%" class="text-center">List</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestResults as $entry)
                    <tr>
                        {{-- Date --}}
                        <td class="text-muted small font-weight-bold">
                            {{ $entry->tournament->start_date->format('d M Y') }}
                        </td>

                        {{-- Place --}}
                        <td class="place-cell">
                            {{ $entry->rank ? Number::ordinal($entry->rank) : '-' }}
                        </td>

                        {{-- Tournament Name --}}
                        <td>
                            <a href="{{ route('admin.tournaments.detail', $entry->tournament_id) }}" class="text-dark font-weight-bold text-decoration-none">
                                {{ $entry->tournament->name }}
                            </a>
                        </td>

                        {{-- Player --}}
                        <td>
                            <span class="text-dark font-weight-bold">
                                {{ $entry->user->nickname ?? $entry->user->name }}
                            </span>
                        </td>

                        {{-- List Icon --}}
                        <td class="text-center">
                            @if($entry->deck)
                                <a href="{{ route('showDeck', $entry->deck->id) }}" class="deck-list-icon" target="_blank">
                                    <i class="far fa-list-alt"></i>
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-history fa-2x mb-3 d-block"></i>
                            No completed tournament results found for this archetype yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="text-center mt-3 mb-4">
                @if(!request('view_all'))
                    @if($latestResults->count() >= 20)
                        <a href="{{ request()->fullUrlWithQuery(['view_all' => 1]) }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm font-weight-bold">
                            Show All Results <i class="fas fa-angle-down ml-1"></i>
                        </a>
                    @endif
                @else
                    <a href="{{ request()->fullUrlWithQuery(['view_all' => null]) }}" class="btn btn-outline-secondary rounded-pill px-4 font-weight-bold">
                        Show Less <i class="fas fa-angle-up ml-1"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- 3. PLAYER STATISTICS TABLE --}}
    <h4 class="section-title">Player Statistics</h4>
    <div class="card shadow-sm border-0 mb-5">
        <div class="table-responsive">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th width="25%" class="pl-4">Player</th>
                        <th width="15%" class="text-center">Tournaments</th>
                        <th width="15%" class="text-center">Games</th>
                        <th width="15%" class="text-center">Wins</th>
                        <th width="15%" class="text-center">Win Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($playerStats as $stat)
                    <tr>
                        <td class="font-weight-bold pl-4 text-dark">
                            {{ $stat['user']->nickname ?? $stat['user']->name }}
                        </td>
                        <td class="text-center">{{ $stat['entries_count'] }}</td>
                        <td class="text-center">{{ $stat['total_matches'] }}</td>
                        <td class="text-center text-success font-weight-bold">{{ $stat['wins'] }}</td>
                        <td class="text-center">
                            @if($stat['win_rate'] >= 50)
                                <span class="badge badge-success px-3 py-2" style="font-size: 0.85rem;">{{ $stat['win_rate'] }}%</span>
                            @else
                                <span class="badge badge-light border text-muted px-3 py-2" style="font-size: 0.85rem;">{{ $stat['win_rate'] }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-users-slash fa-2x mb-3 d-block"></i>
                            No player statistics available.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection