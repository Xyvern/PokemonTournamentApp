@extends('player.layout')

@section('title', $tournament->name)

@section('content')

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="container-fluid">
        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-top-primary">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h1 class="mb-1">{{ $tournament->name }}</h1>
                            <div class="text-muted">
                                <span class="mr-3"><i class="far fa-calendar-alt mr-1"></i> {{ $tournament->start_date->format('d M Y, H:i') }}</span>
                                <span class="mr-3"><i class="fas fa-users mr-1"></i> {{ $tournament->registered_player }} / {{ $tournament->capacity }} Players</span>
                                <span class="mr-3"><i class="fas fa-list-ol mr-1"></i> {{ $tournament->total_rounds }} Rounds</span>
                            </div>
                        </div>
                        <div class="mt-2 mt-md-0">
                            @if($tournament->status === 'registration')
                                <span class="badge badge-primary p-2" style="font-size: 1rem;">Registration Open</span>
                            @elseif($tournament->status === 'active')
                                <span class="badge badge-success p-2" style="font-size: 1rem;">Live - Round {{ $currentRound ?? '?' }}</span>
                            @elseif($tournament->status === 'completed')
                                <span class="badge badge-secondary p-2" style="font-size: 1rem;">Completed</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- LEFT COLUMN: Main Content --}}
            <div class="col-lg-8">
                
                {{-- STATE: REGISTRATION --}}
                @if($tournament->status === 'registration')
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Registration</h5>
                        </div>
                        <div class="card-body">
                            <p class="lead">Join this tournament to compete!</p>
                            @if(isset($myEntry))
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x mr-3"></i>
                                    <div>
                                        <h5 class="mb-0">You are registered!</h5>
                                        <small>Deck: <strong>{{ $myEntry->deck->name ?? 'Unknown Deck' }}</strong></small>
                                    </div>
                                </div>
                                {{-- Optional: Drop button form could go here --}}
                            @elseif($tournament->registered_player >= $tournament->capacity)
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> This tournament has reached full capacity.
                                </div>
                            @else
                                {{-- Register Button (Assuming a route exists) --}}
                                {{-- <form action="{{ route('tournaments.register', $tournament->id) }}" method="POST"> --}}
                                    {{-- @csrf --}}
                                    <button class="btn btn-primary btn-lg px-5" disabled>Register Now (Coming Soon)</button>
                                {{-- </form> --}}
                            @endif
                        </div>
                    </div>
                @endif

                {{-- STATE: ACTIVE (Pairings) --}}
                @if($tournament->status === 'active')
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Current Pairings (Round {{ $currentRound }})</h5>
                            <span class="badge badge-light">Auto-refreshing</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Table</th>
                                            <th>Player 1</th>
                                            <th>Result</th>
                                            <th>Player 2</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($matches ?? [] as $match)
                                            <tr>
                                                <td class="align-middle font-weight-bold text-muted">{{ $match->table_number ?? '-' }}</td>
                                                
                                                {{-- Player 1 --}}
                                                <td class="align-middle {{ $match->result_code === 1 ? 'font-weight-bold text-success' : '' }}">
                                                    {{ $match->player1->user->name ?? 'Unknown' }}
                                                    <span class="badge badge-pill badge-light border ml-1">{{ $match->player1->points }}pts</span>
                                                </td>

                                                {{-- Result --}}
                                                <td class="align-middle text-center">
                                                    @if($match->isReported())
                                                        @if($match->result_code === 1)
                                                            1 - 0
                                                        @elseif($match->result_code === 2)
                                                            0 - 1
                                                        @elseif($match->result_code === 3)
                                                            ½ - ½
                                                        @endif
                                                    @else
                                                        <span class="badge badge-warning">In Progress</span>
                                                    @endif
                                                </td>

                                                {{-- Player 2 --}}
                                                <td class="align-middle {{ $match->result_code === 2 ? 'font-weight-bold text-success' : '' }}">
                                                    @if($match->player2)
                                                        {{ $match->player2->user->name ?? 'Unknown' }}
                                                        <span class="badge badge-pill badge-light border ml-1">{{ $match->player2->points }}pts</span>
                                                    @else
                                                        <span class="text-muted font-italic">Bye</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">Pairings are being generated...</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- STATE: COMPLETED (Standings) --}}
                @if($tournament->status === 'completed' || $tournament->status === 'active')
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">{{ $tournament->status === 'completed' ? 'Final Standings' : 'Current Standings' }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Rank</th>
                                            <th>Player</th>
                                            <th>Deck</th>
                                            <th>Points</th>
                                            <th>OMW%</th>
                                            <th>Record (W-L-T)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tournament->entries ?? [] as $entry)
                                            <tr class="{{ isset($myEntry) && $myEntry->id === $entry->id ? 'table-warning' : '' }}">
                                                <td class="font-weight-bold">{{ $entry->rank ?? '-' }}</td>
                                                <td>
                                                    {{ $entry->user->nickname ?? 'Unknown Player' }}
                                                </td>
                                                <td>
                                                    <span class="text-primary">
                                                        @if ($entry->deck->globalDeck->archetype->name)
                                                            <a href="{{ route('player.showDeck', ['deck' => $entry->deck->id]) }}">
                                                                {{ $entry->deck->globalDeck->archetype->name }}
                                                            </a>
                                                        @else
                                                            Unknown Deck
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="font-weight-bold">{{ $entry->points }}</td>
                                                <td>{{ $entry->omw_percentage }}%</td>
                                                <td>{{ $entry->wins }} - {{ $entry->losses }} - {{ $entry->ties }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No standings available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: Sidebar Info --}}
            <div class="col-lg-4">
                {{-- Your Stats Widget --}}
                @if(isset($myEntry))
                    <div class="card mb-4 bg-light border-info">
                        <div class="card-body">
                            <h5 class="card-title text-info">My Performance</h5>
                            <br>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Rank:</span>
                                <span class="font-weight-bold">{{ $myEntry->rank ?? '-' }} / {{ $tournament->registered_player }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Record:</span>
                                <span class="font-weight-bold">{{ $myEntry->wins }}W - {{ $myEntry->losses }}L - {{ $myEntry->ties }}T</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Points:</span>
                                <span class="font-weight-bold">{{ $myEntry->points }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>ELO Gain:</span>
                                <span class="font-weight-bold {{ $myEntry->total_elo_gain > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $myEntry->total_elo_gain > 0 ? '+' : '' }}{{ $myEntry->total_elo_gain }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">My Match History</h6>
                    </div>
                    <div class="card-body p-0">
                        @if(isset($myEntry) && $myEntry->matches()->count() > 0)
                            <ul class="list-group list-group-flush">
                                {{-- Sort matches by round number descending so newest is top --}}
                                @foreach($myEntry->matches()->sortByDesc('round_number') as $match)
                                    @php
                                        // 1. Determine if I am Player 1 or Player 2
                                        $isPlayer1 = $match->player1_entry_id === $myEntry->id;
                                        
                                        // 2. Get Opponent (TournamentEntry)
                                        $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                                        
                                        // 3. Calculate Result Display
                                        $badgeClass = 'badge-secondary';
                                        $resultText = 'Pending';
                                        
                                        if ($match->result_code !== null) {
                                            if ($match->result_code === 3) {
                                                $badgeClass = 'badge-warning text-white';
                                                $resultText = 'Tie';
                                            } elseif (($isPlayer1 && $match->result_code === 1) || (!$isPlayer1 && $match->result_code === 2)) {
                                                $badgeClass = 'badge-success';
                                                $resultText = 'Win';
                                            } else {
                                                $badgeClass = 'badge-danger';
                                                $resultText = 'Loss';
                                            }
                                        }
                                    @endphp

                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="font-weight-bold text-dark">Round {{ $match->round_number }}</span>
                                                <div class="small text-muted">
                                                    vs {{ $opponent ? $opponent->user->nickname : 'Bye' }} ({{ $match->elo_gain }})
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge {{ $badgeClass }} px-2 py-1">{{ $resultText }}</span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-chess-pawn fa-2x mb-2 text-gray-300"></i>
                                <p class="mb-0">No matches found.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Recent Matches List (Optional sidebar content) --}}
                @if($tournament->status === 'active' && isset($myEntry))
                    <div class="card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Your Match History</h6>
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach($myEntry->matches() as $match)
                                @php
                                    $isP1 = $match->player1_entry_id === $myEntry->id;
                                    $opponent = $isP1 ? $match->player2 : $match->player1;
                                    $myResult = $match->result_code; 
                                    // Logic to determine W/L for me
                                    $resultLabel = 'Pending';
                                    $resultClass = 'badge-light';
                                    if ($match->isReported()) {
                                        if ($match->result_code === 3) { $resultLabel = 'Tie'; $resultClass = 'badge-warning'; }
                                        elseif (($isP1 && $match->result_code === 1) || (!$isP1 && $match->result_code === 2)) { $resultLabel = 'Win'; $resultClass = 'badge-success'; }
                                        else { $resultLabel = 'Loss'; $resultClass = 'badge-danger'; }
                                    }
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <small class="text-muted d-block">Round {{ $match->round_number }}</small>
                                        vs {{ $opponent->user->name ?? 'Bye' }}
                                    </span>
                                    <span class="badge {{ $resultClass }}">{{ $resultLabel }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection