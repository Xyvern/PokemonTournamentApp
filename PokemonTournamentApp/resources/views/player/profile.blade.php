@extends('player.layout')

@section('title', $user->nickname . "'s Profile")

@section('content')

<style>
    /* Custom Bar Chart Styles */
    .stat-bar-container {
        height: 25px;
        background-color: #e9ecef;
        border-radius: 50rem;
        overflow: hidden;
        display: flex;
        width: 100%;
        margin-top: 10px;
    }
    .stat-segment {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
        transition: opacity 0.2s;
        cursor: help;
    }
    .stat-segment:hover { opacity: 0.8; }
    .bg-win { background-color: #28a745; }
    .bg-loss { background-color: #dc3545; }
    .bg-tie { background-color: #6c757d; }

    /* Section Cards */
    .profile-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .archetype-mini-card {
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        border: 1px solid #dee2e6;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. HEADER SECTION --}}
    <div class="profile-card">
        <div class="row align-items-center">
            
            {{-- Left: User Info & Rank --}}
            <div class="col-md-4 text-center text-md-left border-right">
                <h1 class="font-weight-bold mb-0">{{ $user->nickname }}</h1>
                <p class="text-muted mb-3">Joined {{ $user->created_at->format('M Y') }}</p>
                
                {{-- ELO Display --}}
                <div class="d-inline-block bg-light px-4 py-2 rounded-pill border mb-2">
                    <span class="h3 font-weight-bold text-primary mb-0">{{ $user->elo }}</span>
                    <small class="text-muted text-uppercase font-weight-bold ml-2">ELO</small>
                </div>

                {{-- NEW: Server Ranking --}}
                <div class="mb-3">
                    <span class="badge badge-warning text-dark px-3 py-2 shadow-sm" style="font-size: 0.9rem;">
                        <i class="fas fa-trophy mr-1"></i> Server Rank: #{{ $leaderboardRank }}
                    </span>
                </div>

                @if($isOwnProfile)
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-dark btn-sm rounded-pill px-4">
                            <i class="fas fa-cog mr-1"></i> Edit Profile
                        </a>
                    </div>
                @endif
            </div>

            {{-- Middle: Core Stats --}}
            <div class="col-md-4 py-3 py-md-0 px-md-5">
                <div class="row text-center mb-4">
                    <div class="col-6 mb-3">
                        <h5 class="font-weight-bold mb-0">{{ $tournamentsJoined }}</h5>
                        <small class="text-muted text-uppercase">Tournaments</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h5 class="font-weight-bold mb-0">{{ $matchesPlayed }}</h5>
                        <small class="text-muted text-uppercase">Matches</small>
                    </div>
                    <div class="col-6">
                        <h5 class="font-weight-bold mb-0 text-primary">
                            {{ $bestFinish ? Number::ordinal($bestFinish) : '-' }}
                        </h5>
                        <small class="text-muted text-uppercase">Best Finish</small>
                    </div>
                    <div class="col-6">
                        <h5 class="font-weight-bold mb-0">
                            #{{ $averageRank ? round($averageRank, 1) : '-' }}
                        </h5>
                        <small class="text-muted text-uppercase">Avg Rank</small>
                    </div>
                </div>

                {{-- Win/Loss/Tie Bar Chart --}}
                <div class="text-center">
                    <small class="font-weight-bold text-muted">Match Performance</small>
                    <div class="stat-bar-container shadow-sm" title="Wins: {{ $totalWins }} | Losses: {{ $totalLosses }} | Ties: {{ $totalTies }}">
                        @if($winPct > 0)
                            <div class="stat-segment bg-win" style="width: {{ $winPct }}%" data-toggle="tooltip" title="{{ $totalWins }} Wins ({{ $winPct }}%)"></div>
                        @endif
                        @if($lossPct > 0)
                            <div class="stat-segment bg-loss" style="width: {{ $lossPct }}%" data-toggle="tooltip" title="{{ $totalLosses }} Losses ({{ $lossPct }}%)"></div>
                        @endif
                        @if($tiePct > 0)
                            <div class="stat-segment bg-tie" style="width: {{ $tiePct }}%" data-toggle="tooltip" title="{{ $totalTies }} Ties ({{ $tiePct }}%)"></div>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mt-1 small text-muted">
                        <span>{{ $winPct }}% W</span>
                        <span>{{ $totalWins }}W - {{ $totalLosses }}L - {{ $totalTies }}T</span>
                        <span>{{ $lossPct }}% L</span>
                    </div>
                </div>
            </div>

            {{-- Right: Archetype Highlights --}}
            <div class="col-md-4">
                <h6 class="text-uppercase text-muted small font-weight-bold mb-3">Playstyle Analysis</h6>

                {{-- Signature Archetype --}}
                <div class="archetype-mini-card mb-2">
                    @if($signatureArchetype)
                        <img src="{{ $signatureArchetype->keyCard->images->small ?? '' }}" class="rounded mr-3" width="50" style="object-fit: contain;">
                        <div>
                            <small class="text-muted d-block line-height-1">Signature Archetype</small>
                            <span class="font-weight-bold text-dark">{{ $signatureArchetype->name }}</span>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Played in {{ $signatureCount }} tournaments</small>
                        </div>
                    @else
                        <span class="text-muted small pl-2">No signature deck yet.</span>
                    @endif
                </div>

                {{-- Best Winrate Deck --}}
                <div class="archetype-mini-card">
                    @if($bestDeck)
                        <img src="{{ $bestDeck->keyCard->images->small ?? '' }}" class="rounded mr-3" width="50" style="object-fit: contain;">
                        <div>
                            <small class="text-muted d-block line-height-1">Highest Winrate</small>
                            <span class="font-weight-bold text-success">{{ $bestDeck->name }}</span>
                            <small class="text-success d-block font-weight-bold" style="font-size: 0.8rem;">
                                {{ $bestDeck->calculated_win_rate }}% WR
                            </small>
                        </div>
                    @else
                        <span class="text-muted small pl-2">Play more games (3+) to unlock.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 2. LATEST RESULTS TABLE --}}
    <h4 class="mb-3 font-weight-bold border-left pl-3" style="border-width: 4px !important; border-color: #333 !important;">Latest Results (Completed)</h4>
    
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="border-top-0">Date</th>
                        <th class="border-top-0">Place</th>
                        <th class="border-top-0">Tournament</th>
                        <th class="border-top-0">Deck Used</th>
                        <th class="border-top-0 text-center">Record</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestResults as $entry)
                        <tr>
                            <td class="align-middle text-muted small">
                                {{ $entry->tournament->start_date->format('d M Y') }}
                            </td>
                            <td class="align-middle font-weight-bold text-primary">
                                {{ $entry->rank ? Number::ordinal($entry->rank) : '-' }}
                            </td>
                            <td class="align-middle">
                                <a href="{{ route('tournaments.detail', $entry->tournament_id) }}" class="text-dark font-weight-bold text-decoration-none">
                                    {{ $entry->tournament->name }}
                                </a>
                            </td>
                            <td class="align-middle">
                                @if($entry->deck && $entry->deck->globalDeck && $entry->deck->globalDeck->archetype)
                                    <a href="{{ route('showDeck', ['deck' => $entry->deck->id]) }}" class="text-dark font-weight-bold text-decoration-none">
                                        {{ $entry->deck->globalDeck->archetype->name }}
                                    </a>
                                @else
                                    <span class="text-muted font-italic">Unknown Deck</span>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                <span class="badge badge-success">{{ $entry->wins }}W</span>
                                <span class="badge badge-danger">{{ $entry->losses }}L</span>
                                <span class="badge badge-secondary">{{ $entry->ties }}T</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No completed tournament history found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Tooltip Script (Required for Bar Chart hover effects) --}}
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
@endsection