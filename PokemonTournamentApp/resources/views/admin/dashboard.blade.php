@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
<div class="responsive-container">
    
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1 font-weight-bold">Dashboard Overview</h2>
            <p class="text-muted mb-0">High-level metrics and system analytics.</p>
        </div>
    </div>

    {{-- 1. TOP METRIC CARDS --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 border-left-primary h-100" style="border-left: 4px solid #007bff;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            {{-- CHANGED: Now displays Premium / Total --}}
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Premium / Total Players</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">
                                {{ number_format($stats['premium_players']) }} 
                                <span class="text-muted" style="font-size: 1.2rem;">/ {{ number_format($stats['total_players']) }}</span>
                            </div>
                        </div>
                        <i class="fas fa-users fa-2x text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #28a745;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Events</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">{{ number_format($stats['completed_tournaments']) }}</div>
                        </div>
                        <i class="fas fa-trophy fa-2x text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #17a2b8;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tracked Archetypes</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">{{ number_format($stats['total_archetypes']) }}</div>
                        </div>
                        <i class="fas fa-layer-group fa-2x text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ffc107;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Player Decks</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">{{ number_format($stats['total_decks']) }}</div>
                        </div>
                        <i class="fas fa-box-open fa-2x text-muted opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. CHARTS ROW 1 (Pie & Bar) --}}
    <div class="row mb-4">
        {{-- Popular Archetypes Doughnut Chart --}}
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-chart-pie text-primary mr-2"></i> Most Played Archetypes</h6>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <div style="position: relative; height:250px; width:100%">
                        <canvas id="popularArchetypesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Archetype Win Rates Bar Chart --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-chart-bar text-success mr-2"></i> Top Meta Win Rates (%)</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height:250px; width:100%">
                        <canvas id="winRateChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. CHARTS ROW 2 (Line Chart) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-4 pb-0 d-flex align-items-center">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-chart-line text-info mr-2"></i> Tournament Attendance Trends
                    </h6>
                    <a href="{{ route('admin.tournaments.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill ml-auto">
                        View All Tournaments
                    </a>
                </div>
                
                <div class="card-body">
                    <div style="position: relative; height:300px; width:100%">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. NEW: TRANSACTION HISTORY & PLAYER REPORT ROW --}}
    <div class="row mb-5">
        
        {{-- Recent Transactions --}}
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-receipt text-secondary mr-2"></i> Recent Subscriptions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $tx)
                                    <tr>
                                        <td class="text-muted small align-middle">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                        <td class="font-weight-bold align-middle">{{ $tx->user->nickname ?? 'Unknown' }}</td>
                                        <td class="align-middle">Rp {{ number_format($tx->gross_amount, 0, ',', '.') }}</td>
                                        <td class="align-middle">
                                            @if($tx->status == 'settlement' || $tx->status == 'capture')
                                                <span class="badge badge-success">Success</span>
                                            @elseif($tx->status == 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @else
                                                <span class="badge badge-danger">{{ ucfirst($tx->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No transactions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Player Competitive Report --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-3 d-flex align-items-center">
                    <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-medal text-warning mr-2"></i> Player Leaderboard & Report</h6>
                    <a href="{{ route('admin.players.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill ml-auto">Manage</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0" id="playerTable">
                            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => request('sort') == 'name' && request('dir') == 'asc' ? 'desc' : 'asc']) }}" class="text-dark text-decoration-none">
                                            Player 
                                            <i class="fas fa-sort{{ request('sort') == 'name' ? (request('dir') == 'asc' ? '-up' : '-down') : ' text-muted' }} ml-1"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'elo', 'dir' => request('sort', 'elo') == 'elo' && request('dir', 'desc') == 'desc' ? 'asc' : 'desc']) }}" class="text-dark text-decoration-none">
                                            ELO 
                                            <i class="fas fa-sort{{ request('sort', 'elo') == 'elo' ? (request('dir', 'desc') == 'asc' ? '-up' : '-down') : ' text-muted' }} ml-1"></i>
                                        </a>
                                    </th>
                                    <th class="text-center">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'rank_1', 'dir' => request('sort') == 'rank_1' && request('dir') == 'desc' ? 'asc' : 'desc']) }}" class="text-dark text-decoration-none">
                                            Rank 1 Wins 
                                            <i class="fas fa-sort{{ request('sort') == 'rank_1' ? (request('dir') == 'asc' ? '-up' : '-down') : ' text-muted' }} ml-1"></i>
                                        </a>
                                    </th>
                                    <th class="text-center">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'sessions', 'dir' => request('sort') == 'sessions' && request('dir') == 'desc' ? 'asc' : 'desc']) }}" class="text-dark text-decoration-none">
                                            Sessions 
                                            <i class="fas fa-sort{{ request('sort') == 'sessions' ? (request('dir') == 'asc' ? '-up' : '-down') : ' text-muted' }} ml-1"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'last_active', 'dir' => request('sort') == 'last_active' && request('dir') == 'desc' ? 'asc' : 'desc']) }}" class="text-dark text-decoration-none">
                                            Last Active 
                                            <i class="fas fa-sort{{ request('sort') == 'last_active' ? (request('dir') == 'asc' ? '-up' : '-down') : ' text-muted' }} ml-1"></i>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($playerReports as $player)
                                    <tr>
                                        {{-- data-sort allows JS to sort raw data instead of HTML tags --}}
                                        <td data-sort="{{ strtolower($player->nickname) }}" class="font-weight-bold align-middle">
                                            {{ $player->nickname }}
                                        </td>
                                        
                                        <td data-sort="{{ $player->elo }}" class="align-middle">
                                            <span class="badge badge-primary px-2 py-1">{{ $player->elo }}</span>
                                        </td>
                                        
                                        <td data-sort="{{ $player->rank_1_count }}" class="align-middle text-center">
                                            @if($player->rank_1_count > 0)
                                                <i class="fas fa-trophy text-warning mr-1"></i> {{ $player->rank_1_count }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        
                                        <td data-sort="{{ $player->total_sessions }}" class="align-middle text-center">
                                            {{ $player->total_sessions }}
                                        </td>
                                        
                                        {{-- Using strtotime() converts the date to a simple number that is very easy for JS to sort --}}
                                        <td data-sort="{{ $player->tournament_entries_max_created_at ? strtotime($player->tournament_entries_max_created_at) : 0 }}" class="text-muted small align-middle">
                                            {{ $player->tournament_entries_max_created_at ? \Carbon\Carbon::parse($player->tournament_entries_max_created_at)->format('d M Y') : 'Never' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No player data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
    {{-- NEW: PHOTON SERVER REPORT --}}
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="h4 font-weight-bold" style="margin-top: 2vh; border-bottom: 2px solid #6f42c1; padding-bottom: 10px;">Photon Server Report (Webhooks)</h2>
            <div class="card shadow-sm border-left-{{ $photonStats['status_color'] }} h-100" style="border-left: 4px solid var(--{{ $photonStats['status_color'] }});">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 border-right mb-3 mb-md-0 text-center">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #6f42c1;">Active Tournaments</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">{{ $allActiveTournaments->count() }}</div>
                        </div>
                        <div class="col-md-3 border-right mb-3 mb-md-0 text-center">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-info">Required CCU (Min)</div>
                            <div class="h3 mb-0 font-weight-bold text-dark" title="Active Round Matches x 2 Players">{{ $photonStats['required_ccu'] }}</div>
                        </div>
                        <div class="col-md-3 border-right mb-3 mb-md-0 text-center">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-{{ $photonStats['status_color'] }}">Current / Max CCU</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">
                                {{ $photonStats['current_ccu'] }} <span class="text-muted" style="font-size: 1.2rem;">/ {{ $photonStats['max_ccu'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="text-xs font-weight-bold text-uppercase mb-1 text-secondary">Server Forecast</div>
                            <div class="font-weight-bold text-{{ $photonStats['status_color'] }}">
                                {{ $photonStats['forecast'] }}
                            </div>
                        </div>
                    </div>
                    
                    {{-- Connected Users List --}}
                    @if(!empty($connectedUsers) && count($connectedUsers) > 0)
                        <hr class="mt-4 mb-3">
                        <div class="text-xs font-weight-bold text-uppercase mb-2 text-muted">Currently Connected Players</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($connectedUsers as $user)
                                <span class="badge badge-light border px-2 py-1 mr-2 mb-2" style="font-size: 0.9rem;">
                                    <i class="fas fa-user-circle text-primary mr-1"></i> {{ $user->nickname }}
                                    <span class="text-muted ml-1" style="font-size: 0.75rem;">(ELO: {{ $user->elo }})</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 5. ONGOING & UPCOMING TOURNAMENTS --}}
    <div class="row mb-5">
        {{-- Active Tournaments --}}
        <div class="col-md-6 mb-4 mb-md-0">
            {{-- CHANGED: Updated Title and Border Color to Primary Blue --}}
            <h2 class="h4 font-weight-bold" style="margin-top: 2vh; border-bottom: 2px solid #007bff; padding-bottom: 10px;">Active Tournaments</h2>
            
            @if ($activeTournamentsView->isEmpty())
                <div class="alert alert-light border" style="margin-top: 2vh;">No active tournaments found.</div>
            @else
                <div class="d-flex flex-column gap-3" style="margin-top: 2vh;">
                    @foreach ($activeTournamentsView as $item)
                        <a href="{{ route('admin.tournaments.detail', ['id' => $item->id]) }}" style="text-decoration: none; color: inherit;">
                            <div class="info-box shadow-sm mb-3 hover-lift" style="border-radius: 8px; transition: transform 0.2s;">
                                {{-- CHANGED: Replaced bg-secondary with bg-primary and made text white --}}
                                <span class="info-box-icon bg-primary d-flex flex-column justify-content-center align-items-center rounded-left" style="font-size: 1.5rem; min-width: 80px;">
                                    <span style="font-size: 1.5rem; font-weight: bold; color: white;">{{ $item->capacity }}</span>
                                    <span style="font-size: 0.8rem; color: white;">Max</span>
                                </span>

                                <div class="info-box-content p-3">
                                    <span class="info-box-text text-dark" style="font-weight: bold; font-size: 1.1rem;">{{ $item->name }}</span>
                                    <div class="info-box-number text-muted mt-1" style="font-weight: 500; font-size: 0.9rem;">
                                        <p class="mb-1"><i class="fas fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}</p>
                                        <p class="mb-0"><i class="fas fa-users mr-1"></i> {{ $item->registered_player }}/{{ $item->capacity }} players</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <div class="text-right mt-2">
                    {{-- CHANGED: Button is now btn-outline-primary and filters by active --}}
                    <a href="{{ route('admin.tournaments.index', ['filter' => 'active']) }}" class="btn btn-sm btn-outline-primary rounded-pill">See all active tournaments &rarr;</a>
                </div>
            @endif
        </div>

        {{-- Upcoming Tournaments --}}
        <div class="col-md-6">
            <h2 class="h4 font-weight-bold" style="margin-top: 2vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">Upcoming Tournaments</h2>

            @if ($upcomingTournaments->isEmpty())
                <div class="alert alert-light border" style="margin-top: 2vh;">No upcoming tournaments found.</div>
            @else
                <div class="d-flex flex-column gap-3" style="margin-top: 2vh;">
                    @foreach ($upcomingTournaments as $item)
                        <a href="{{ route('admin.tournaments.detail', ['id' => $item->id]) }}" style="text-decoration: none; color: inherit;">
                            <div class="info-box shadow-sm mb-3 hover-lift" style="border-radius: 8px; transition: transform 0.2s;">
                                <span class="info-box-icon bg-info d-flex flex-column justify-content-center align-items-center rounded-left" style="font-size: 1.5rem; min-width: 80px;">
                                    <span style="font-size: 1.5rem; font-weight: bold; color: white;">{{ $item->capacity }}</span>
                                    <span style="font-size: 0.8rem; color: white;">Max</span>
                                </span>

                                <div class="info-box-content p-3">
                                    <span class="info-box-text text-dark" style="font-weight: bold; font-size: 1.1rem;">{{ $item->name }}</span>
                                    <div class="info-box-number text-muted mt-1" style="font-weight: 500; font-size: 0.9rem;">
                                        <p class="mb-1"><i class="fas fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}</p>
                                        <p class="mb-0"><i class="fas fa-users mr-1"></i> {{ $item->registered_player }}/{{ $item->capacity }} players</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="text-right mt-2">
                    <a href="{{ route('admin.tournaments.index', ['filter' => 'upcoming']) }}" class="btn btn-sm btn-outline-info rounded-pill">See all upcoming tournaments &rarr;</a>
                </div>
            @endif
        </div>
    </div> 
</div>
@endsection

@push('scripts')
{{-- Load Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Standard Modern Chart Colors
        const chartColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];

        // 1. Popular Archetypes Doughnut Chart
        const ctxPie = document.getElementById('popularArchetypesChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($popularChartLabels) !!},
                datasets: [{
                    data: {!! json_encode($popularChartData) !!},
                    backgroundColor: chartColors,
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                },
                cutout: '70%',
            },
        });

        // 2. Archetype Win Rates Bar Chart
        const ctxBar = document.getElementById('winRateChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: {!! json_encode($winRateLabels) !!},
                datasets: [{
                    label: 'Win Rate (%)',
                    data: {!! json_encode($winRateData) !!},
                    backgroundColor: '#1cc88a',
                    borderRadius: 4,
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true, 
                        max: 100,
                        grid: { borderDash: [2, 2] } 
                    },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            },
        });

        // 3. Tournament Attendance Line Chart
        const ctxLine = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: {!! json_encode($attendanceLabels) !!},
                datasets: [{
                    label: 'Players Registered',
                    data: {!! json_encode($attendanceData) !!},
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.3 // Adds a slight curve to the line
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        grid: { borderDash: [2, 2] },
                        ticks: { stepSize: 4 }
                    },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            },
        });
    });
</script>
@endpush