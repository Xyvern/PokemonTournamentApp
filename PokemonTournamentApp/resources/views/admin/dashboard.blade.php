@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
<div style="margin-left: 10vw; margin-top: 2vh; margin-right: 10vw;">
    
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Players</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">{{ number_format($stats['total_players']) }}</div>
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
                            {{-- CHANGED: Updated Title and Variable --}}
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Player Decks</div>
                            <div class="h3 mb-0 font-weight-bold text-dark">{{ number_format($stats['total_decks']) }}</div>
                        </div>
                        {{-- CHANGED: Swapped the icon to a stacked box/deck icon --}}
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
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                
                {{-- CHANGED: Removed justify-content-between, added ml-auto to the button --}}
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