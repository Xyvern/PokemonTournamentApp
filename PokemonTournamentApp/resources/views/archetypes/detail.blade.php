@extends('player.layout')

@section('title', $archetype->name . ' Detail')

@section('content')

<style>
    /* Custom Table Styling to match reference image */
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
        color: #0056b3; /* Limitless-like blue */
    }
    .deck-list-icon {
        color: #333;
        font-size: 1.2rem;
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

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. HEADER SECTION --}}
    <div class="text-center mb-5">
        <div class="mb-3 d-inline-block shadow-sm rounded overflow-hidden" style="width: 200px;">
            <img src="{{ $archetype->keyCard->images->large ?? $archetype->keyCard->images->small }}" 
                 alt="{{ $archetype->name }}" 
                 class="img-fluid">
        </div>

        <h1 class="font-weight-bold display-4">{{ $archetype->name }}</h1>

        <div class="d-flex justify-content-center gap-4 mt-3">
            <div class="px-4 py-2 bg-light rounded shadow-sm border">
                <span class="d-block text-muted small text-uppercase">Total Games</span>
                <span class="h4 font-weight-bold text-primary">{{ $archetype->times_played }}</span>
            </div>
            <div class="px-4 py-2 bg-light rounded shadow-sm border">
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
                        <td class="text-muted small">
                            {{ $entry->tournament->start_date->format('d M Y') }}
                        </td>

                        {{-- Place --}}
                        <td class="place-cell">
                            {{ Number::ordinal($entry->rank ?? 0) }}
                        </td>

                        {{-- Tournament Name --}}
                        <td>
                            <a href="{{ route('tournaments.detail', $entry->tournament_id) }}" class="text-dark font-weight-bold text-decoration-none">
                                {{ $entry->tournament->name }}
                            </a>
                        </td>

                        {{-- Player --}}
                        <td>
                            <a href="{{ route('player.profile', $entry->user_id) }}" class="text-dark font-weight-bold text-decoration-none">
                                {{ $entry->user->nickname ?? $entry->user->name }}
                            </a>
                        </td>

                        {{-- List Icon --}}
                        <td class="text-center">
                            @if($entry->deck)
                                <a href="{{ route('showDeck', $entry->deck->id) }}" class="deck-list-icon">
                                    <i class="far fa-list-alt"></i>
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No tournament results found for this archetype.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="text-center mt-3 mb-4">
                @if(!request('view_all'))
                    {{-- Only show button if we actually hit the limit of 20 --}}
                    @if($latestResults->count() >= 20)
                        <a href="{{ request()->fullUrlWithQuery(['view_all' => 1]) }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                            Show All Results <i class="fas fa-angle-down ml-1"></i>
                        </a>
                    @endif
                @else
                    {{-- Link to remove the view_all parameter --}}
                    <a href="{{ request()->fullUrlWithQuery(['view_all' => null]) }}" class="btn btn-outline-secondary rounded-pill px-4">
                        Show Less <i class="fas fa-angle-up ml-1"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- 3. PLAYER STATISTICS TABLE --}}
    <h4 class="section-title">Player Statistics</h4>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th width="25%">Player</th>
                        <th width="15%" class="text-center">Tournaments</th>
                        <th width="15%" class="text-center">Games</th>
                        <th width="15%" class="text-center">Wins</th>
                        <th width="15%" class="text-center">Win Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($playerStats as $stat)
                    <tr>
                        {{-- Player --}}
                        <td class="font-weight-bold">
                            {{ $stat['user']->nickname ?? $stat['user']->name }}
                        </td>

                        {{-- Tournaments Count --}}
                        <td class="text-center">{{ $stat['entries_count'] }}</td>

                        {{-- Total Games --}}
                        <td class="text-center">{{ $stat['total_matches'] }}</td>

                        {{-- Wins --}}
                        <td class="text-center text-success font-weight-bold">{{ $stat['wins'] }}</td>

                        {{-- Win Rate --}}
                        <td class="text-center">
                            @if($stat['win_rate'] >= 50)
                                <span class="badge badge-success p-2">{{ $stat['win_rate'] }}%</span>
                            @else
                                <span class="badge badge-light border p-2">{{ $stat['win_rate'] }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No player statistics available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection