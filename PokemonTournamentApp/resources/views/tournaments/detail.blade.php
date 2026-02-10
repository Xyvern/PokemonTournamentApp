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
                @if($tournament->status === 'active' || $tournament->status === 'completed')
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 mr-3">Pairings</h5>
                                
                                {{-- Round Pagination Controls --}}
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-prev-round" onclick="changeRound(-1)">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary disabled" id="round-display">
                                        Round <span id="current-round-text">{{ $currentRound }}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btn-next-round" onclick="changeRound(1)">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <span class="badge badge-light" id="loading-badge" style="display:none;">Loading...</span>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Player 1</th>
                                            <th>Player 2</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody id="matches-table-body">
                                        {{-- Load the partial initially for the current round --}}
                                        @include('tournaments.partials.matches_rows', ['matches' => $matches])
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- AJAX Script --}}
                    <script>
                        // Initialize State
                        let currentRound = {{ $currentRound }};
                        // If tournament is active, max round is current. If completed, it's total rounds.
                        const maxRound = {{ $tournament->status === 'completed' ? $tournament->total_rounds : $currentRound }}; 
                        const tournamentId = {{ $tournament->id }};

                        function updateButtons() {
                            // Disable Prev if at round 1
                            document.getElementById('btn-prev-round').disabled = (currentRound <= 1);
                            // Disable Next if at max round
                            document.getElementById('btn-next-round').disabled = (currentRound >= maxRound);
                            // Update Text
                            document.getElementById('current-round-text').innerText = currentRound;
                        }

                        function changeRound(direction) {
                            let newRound = currentRound + direction;

                            if (newRound < 1 || newRound > maxRound) return;

                            currentRound = newRound;
                            updateButtons();
                            loadMatches(currentRound);
                        }

                        function loadMatches(round) {
                            // Show loading
                            document.getElementById('loading-badge').style.display = 'inline-block';
                            document.getElementById('matches-table-body').style.opacity = '0.5';

                            // URL generation
                            let url = "{{ route('tournaments.matches.fetch', ':id') }}";
                            url = url.replace(':id', tournamentId) + "?round=" + round;

                            fetch(url)
                                .then(response => response.text())
                                .then(html => {
                                    // Replace table body
                                    document.getElementById('matches-table-body').innerHTML = html;
                                    
                                    // Hide loading
                                    document.getElementById('loading-badge').style.display = 'none';
                                    document.getElementById('matches-table-body').style.opacity = '1';
                                })
                                .catch(error => {
                                    console.error('Error fetching matches:', error);
                                    document.getElementById('loading-badge').innerText = 'Error';
                                });
                        }

                        // Run once on load to set button states
                        document.addEventListener("DOMContentLoaded", function() {
                            updateButtons();
                        });
                    </script>
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
                                                        @if ($entry->deck->globalDeck->archetype?->name)
                                                            <a href="{{ route('showDeck', ['deck' => $entry->deck->id]) }}">
                                                                {{ $entry->deck->globalDeck->archetype->name }}
                                                            </a>
                                                        @else
                                                            <a href="{{ route('showDeck', ['deck' => $entry->deck->id]) }}">
                                                                Unknown Deck Archetype
                                                            </a>
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
                                        $badgeClass = 'badge-warning';
                                        $resultText = 'In Progress';
                                        
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
            </div>
        </div>
    </div>
</div>
@endsection