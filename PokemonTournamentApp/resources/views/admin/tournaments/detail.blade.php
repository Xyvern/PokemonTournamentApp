@extends('admin.layout')

@section('title', $tournament->name . ' - Admin Management')

@section('content')

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="container-fluid">
        {{-- 1. HEADER SECTION --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-top-primary">
                    <div class="card-body d-flex align-items-center flex-wrap"> 
                        <div>
                            <h1 class="mb-1">{{ $tournament->name }}</h1>
                            <div class="text-muted">
                                <span class="mr-3"><i class="far fa-calendar-alt mr-1"></i> {{ $tournament->start_date->format('d M Y, H:i') }}</span>
                                <span class="mr-3"><i class="fas fa-users mr-1"></i> {{ $tournament->registered_player }} / {{ $tournament->capacity }} Players</span>
                                <span class="mr-3"><i class="fas fa-list-ol mr-1"></i> {{ $tournament->total_rounds }} Rounds</span>
                            </div>
                        </div>

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
            {{-- LEFT COLUMN: Pairings & Standings --}}
            <div class="col-lg-8">
                
                {{-- PAIRINGS MANAGEMENT --}}
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 mr-3">Pairings</h5>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" id="btn-prev-round" onclick="changeRound(-1)"><i class="fas fa-chevron-left"></i></button>
                                <button type="button" class="btn btn-outline-secondary disabled" id="round-display">Round <span id="current-round-text">{{ $currentRound }}</span></button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-next-round" onclick="changeRound(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Table</th>
                                        <th>Player 1</th>
                                        <th>Player 2</th>
                                        <th>Result</th>
                                        <th>Admin Action</th>
                                        <th>Spectate</th> {{-- Added New Column Here --}}
                                    </tr>
                                </thead>
                                <tbody id="matches-table-body">
                                    {{-- NOTE: You will need to update your partial to include an 'Edit Result' button instead of 'Watch' --}}
                                    @include('admin.tournaments.partials.matches_rows', ['matches' => $matches, 'isAdmin' => true])
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- CURRENT STANDINGS --}}
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
                                        <tr>
                                            <td class="font-weight-bold">{{ $entry->rank ?? '-' }}</td>
                                            <td>{{ $entry->user->nickname ?? 'Unknown' }}</td>
                                            <td>
                                                @if ($entry->deck->globalDeck->archetype?->name)
                                                    <a href="{{ route('showDeck', ['deck' => $entry->deck->id]) }}" target="_blank">{{ $entry->deck->globalDeck->archetype->name }}</a>
                                                @else
                                                    <span class="text-muted">Unknown</span>
                                                @endif
                                            </td>
                                            <td class="font-weight-bold">{{ $entry->points }}</td>
                                            <td>{{ $entry->omw_percentage }}%</td>
                                            <td>{{ $entry->wins }} - {{ $entry->losses }} - {{ $entry->ties }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No standings available yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Admin Controls & Meta --}}
            <div class="col-lg-4">
                
                {{-- TOURNAMENT CONTROLS PANEL --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Organizer Controls</h5>
                    </div>
                    <div class="card-body">
                        @if($tournament->status === 'registration')
                            <p class="small text-muted mb-3">Registration is currently open. Once ready, generate the first round of pairings.</p>
                            <form action="{{ route('admin.tournaments.start', $tournament->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block mb-3">
                                    Start Tournament (Round 1)
                                </button>
                            </form>
                        @elseif($tournament->status === 'active')
                            <p class="small text-muted mb-3">Ensure all match results are reported before proceeding to the next round.</p>
                            <form action="{{ route('admin.tournaments.nextRound', $tournament->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block mb-3">
                                    Generate Next Round Pairings
                                </button>
                            </form>
                            <form action="{{ route('admin.tournaments.finalize', $tournament->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block mb-3">
                                    Finalize Tournament
                                </button>
                            </form>
                            <hr>
                            
                            {{-- Drop Player Button (Opens Modal) --}}
                            <button type="button" class="btn btn-outline-danger btn-block" data-toggle="modal" data-target="#dropPlayerModal" {{ $tournament->status === 'completed' ? 'disabled' : '' }}>
                                Drop Player
                            </button>
                        @endif
                    </div>
                </div>

                {{-- METAGAME SHARE --}}
                @if (!empty($metaLabels))
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 font-weight-bold text-dark">Metagame Share</h6>
                        </div>
                        <div class="card-body pt-0 mt-3">
                            <div style="position: relative; height: 250px; width: 100%;">
                                <canvas id="metaShareChart"></canvas>
                            </div>
                            <div class="mt-3 text-center small text-muted">
                                Total Decks: <strong>{{ array_sum($metaData) }}</strong>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ==========================================
     MODALS FOR ADMIN ACTIONS
=========================================== --}}

{{-- 1. Drop Player Modal --}}
<div class="modal fade" id="dropPlayerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.tournaments.dropPlayer', $tournament->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Drop Player from Tournament</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Select a player to drop. They will receive losses for all remaining rounds.</p>
                    <div class="form-group">
                        <label>Select Player</label>
                        <select name="entry_id" class="form-control" required>
                            <option value="" disabled selected>-- Choose Player --</option>
                            {{-- THE FIX: Updated to 'is_dropped' to match your new migration --}}
                            @foreach($tournament->entries->where('is_dropped', false) as $entry)
                                <option value="{{ $entry->id }}">{{ $entry->user->nickname }} ({{ $entry->points }} pts)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Drop</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 2. Edit Match Result Modal --}}
<div class="modal fade" id="editMatchModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.tournaments.updateMatch', $tournament->id) }}" method="POST" id="editMatchForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="match_id" id="edit_match_id">
                <div class="modal-header">
                    <h5 class="modal-title">Fix Match Result</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p id="match-players-text" class="font-weight-bold text-center"></p>
                    <div class="form-group">
                        <label>Set Result</label>
                        <select name="result_code" class="form-control" required>
                            <option value="1">Player 1 Wins</option>
                            <option value="2">Player 2 Wins</option>
                            <option value="3">Tie / Draw</option>
                            <option value="">Reset to In-Progress</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Override</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        document.getElementById('matches-table-body').style.opacity = '0.5';
        let url = "{{ route('admin.tournaments.matches.fetch', ':id') }}".replace(':id', tournamentId) + "?round=" + round + "&admin=true";

        fetch(url)
            .then(response => response.text())
            .then(html => {
                document.getElementById('matches-table-body').innerHTML = html;
                document.getElementById('matches-table-body').style.opacity = '1';
            });
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateButtons();
        
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

    // Helper function to be called from matches_rows partial
    function openEditMatchModal(matchId, p1Name, p2Name) {
        document.getElementById('edit_match_id').value = matchId;
        document.getElementById('match-players-text').innerText = `${p1Name} vs ${p2Name}`;
        $('#editMatchModal').modal('show');
    }
</script>
@endsection