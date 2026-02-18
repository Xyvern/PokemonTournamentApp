@extends('player.layout')

@section('title', $tournament->name)

@section('content')

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="container-fluid">
        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-top-primary">
                    <div class="card-body d-flex align-items-center flex-wrap"> 
                        {{-- 1. Main Content --}}
                        <div>
                            <h1 class="mb-1">{{ $tournament->name }}</h1>
                            <div class="text-muted">
                                <span class="mr-3"><i class="far fa-calendar-alt mr-1"></i> {{ $tournament->start_date->format('d M Y, H:i') }}</span>
                                <span class="mr-3"><i class="fas fa-users mr-1"></i> {{ $tournament->registered_player }} / {{ $tournament->capacity }} Players</span>
                                <span class="mr-3"><i class="fas fa-list-ol mr-1"></i> {{ $tournament->total_rounds }} Rounds</span>
                            </div>
                        </div>

                        {{-- 2. Badge --}}
                        <div class="mt-2 mt-md-0 ml-auto"> 
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

                            {{-- CHECK 1: IS USER LOGGED IN? --}}
                            @guest
                                <div class="alert alert-light border text-center py-4">
                                    <i class="fas fa-lock fa-2x mb-3 text-muted"></i>
                                    <h5>Login Required</h5>
                                    <p class="text-muted mb-3">You must be logged in to register for this tournament.</p>
                                    <a href="{{ route('login') }}" class="btn btn-primary px-4">
                                        Login / Register
                                    </a>
                                </div>
                            @endguest

                            {{-- CHECK 2: AUTHENTICATED USER --}}
                            @auth
                                @if(isset($myEntry))
                                    {{-- ALREADY REGISTERED --}}
                                    <div class="alert alert-success d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x mr-3"></i>
                                        <div>
                                            <h5 class="mb-0">You are registered!</h5>
                                            <small>Deck: <strong>{{ $myEntry->deck->name ?? 'Unknown Deck' }}</strong></small>
                                        </div>
                                    </div>
                                    <form action="{{ route('tournaments.drop', $tournament->id) }}" method="post">
                                        @csrf
                                        <input type="hidden" name="entry_id" value="{{ $myEntry->id }}">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to drop?');">Drop Tournament</button>
                                    </form>

                                @elseif($tournament->registered_player >= $tournament->capacity)
                                    {{-- CAPACITY FULL --}}
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> This tournament has reached full capacity.
                                    </div>

                                @else
                                    {{-- REGISTRATION FORM --}}
                                    <form action="{{ route('tournaments.register', $tournament->id) }}" method="POST">
                                        @csrf
                                        
                                        {{-- Player Name (Safe because we are inside @auth) --}}
                                        <div class="form-group mb-3">
                                            <label for="nickname" class="font-weight-bold small text-uppercase text-muted">Player Name</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-light border-right-0"><i class="fas fa-user text-muted"></i></span>
                                                </div>
                                                <input type="text" class="form-control bg-light border-left-0" value="{{ Auth::user()->nickname }}" disabled readonly style="color: #495057; font-weight: 600;">
                                            </div>
                                        </div>

                                        {{-- Deck Selection --}}
                                        <div class="form-group mb-4">
                                            <label for="deck-select" class="font-weight-bold small text-uppercase text-muted">Select Your Deck</label>
                                            
                                            @if(isset($myDeck) && $myDeck->isNotEmpty())
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-white"><i class="fas fa-layer-group text-primary"></i></span>
                                                    </div>
                                                    <select name="deck_id" id="deck-select" class="form-control custom-select" required>
                                                        <option value="" disabled selected>-- Choose a Deck --</option>
                                                        @foreach($myDeck as $deck)
                                                            <option value="{{ $deck->id }}">{{ $deck->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <small class="form-text text-muted">Select the deck you will use for all rounds.</small>
                                            @else
                                                <div class="alert alert-warning d-flex align-items-center mb-0 p-2" role="alert">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <span style="font-size: 0.9rem;">You need to create a deck first.</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Submit Button --}}
                                        <button type="submit" class="btn btn-primary btn-block w-100 font-weight-bold py-2 shadow-sm" {{ (!isset($myDeck) || $myDeck->isEmpty()) ? 'disabled' : '' }}>
                                            Register Now <i class="fas fa-arrow-right ml-1"></i>
                                        </button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </div>
                @endif

                {{-- STATE: ACTIVE (Pairings) --}}
                @if($tournament->status === 'active' || $tournament->status === 'completed')
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 mr-3">Pairings</h5>
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
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="matches-table-body">
                                        @include('tournaments.partials.matches_rows', ['matches' => $matches])
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- AJAX Script --}}
                    <script>
                        let currentRound = {{ $currentRound }};
                        const maxRound = {{ $tournament->status === 'completed' ? $tournament->total_rounds : $currentRound }}; 
                        const tournamentId = {{ $tournament->id }};

                        function updateButtons() {
                            document.getElementById('btn-prev-round').disabled = (currentRound <= 1);
                            document.getElementById('btn-next-round').disabled = (currentRound >= maxRound);
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
                            document.getElementById('loading-badge').style.display = 'inline-block';
                            document.getElementById('matches-table-body').style.opacity = '0.5';

                            let url = "{{ route('tournaments.matches.fetch', ':id') }}";
                            url = url.replace(':id', tournamentId) + "?round=" + round;

                            fetch(url)
                                .then(response => response.text())
                                .then(html => {
                                    document.getElementById('matches-table-body').innerHTML = html;
                                    document.getElementById('loading-badge').style.display = 'none';
                                    document.getElementById('matches-table-body').style.opacity = '1';
                                })
                                .catch(error => {
                                    console.error('Error fetching matches:', error);
                                    document.getElementById('loading-badge').innerText = 'Error';
                                });
                        }

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
                                            @if ($tournament->status === 'completed')
                                                <th>Deck</th>
                                            @endif
                                            <th>Points</th>
                                            <th>OMW%</th>
                                            <th>Record (W-L-T)</th>
                                            @if ($tournament->status === 'completed')
                                                <th>Elo Gain/Loss</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tournament->entries ?? [] as $entry)
                                            <tr class="{{ isset($myEntry) && $myEntry->id === $entry->id ? 'table-warning' : '' }}">
                                                <td class="font-weight-bold">{{ $entry->rank ?? '-' }}</td>
                                                <td>{{ $entry->user->nickname ?? 'Unknown Player' }}</td>
                                                @if ($tournament->status === 'completed')
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
                                                @endif
                                                <td class="font-weight-bold">{{ $entry->points }}</td>
                                                <td>{{ $entry->omw_percentage }}%</td>
                                                <td>{{ $entry->wins }} - {{ $entry->losses }} - {{ $entry->ties }}</td>
                                                @if ($tournament->status === 'completed')
                                                    <td class="font-weight-bold {{ $entry->total_elo_gain > 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $entry->total_elo_gain > 0 ? '+' : '' }}{{ $entry->total_elo_gain }}
                                                    </td>
                                                @endif
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
                @if ($tournament->status === 'completed' && !empty($metaLabels))
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-white border-bottom-0">
                            <h6 class="mb-0 font-weight-bold text-dark">
                                <i class="fas fa-chart-pie mr-2 text-primary"></i>Metagame Share
                            </h6>
                        </div>
                        <div class="card-body pt-0">
                            <div style="position: relative; height: 250px; width: 100%;">
                                <canvas id="metaShareChart"></canvas>
                            </div>
                            <div class="mt-3 text-center small text-muted">
                                Total Decks: <strong>{{ array_sum($metaData) }}</strong>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Your Stats Widget (Only show if logged in AND registered) --}}
                @auth
                    @if(isset($myEntry))
                        <div class="card mb-4 bg-light border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info">My Performance</h5>
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

                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">My Match History</h6>
                            </div>
                            <div class="card-body p-0">
                                @if($myEntry->matches()->count() > 0)
                                    <ul class="list-group list-group-flush">
                                        @foreach($myEntry->matches()->sortByDesc('round_number') as $match)
                                            @php
                                                $isPlayer1 = $match->player1_entry_id === $myEntry->id;
                                                $opponent = $isPlayer1 ? $match->player2 : $match->player1;
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
                    @endif
                @endauth
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const labels = @json($metaLabels ?? []);
        const data = @json($metaData ?? []);

        if (labels.length > 0) {
            const ctx = document.getElementById('metaShareChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut', 
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                            '#FF9F40', '#E7E9ED', '#71B37C', '#C9CBCF', '#E6B33D'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = Math.round((value / total) * 100) + '%';
                                    return label + ': ' + value + ' (' + percentage + ')';
                                }
                            }
                        }
                    },
                    layout: { padding: 0 }
                }
            });
        }
    });
</script>
@endsection