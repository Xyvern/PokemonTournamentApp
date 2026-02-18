@extends('player.layout')

@section('title', 'All Tournaments')

@section('content')
<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. PAGE HEADER & FILTERS --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">Tournaments</h2>
                            <p class="text-muted mb-0">Find and register for upcoming events</p>
                        </div>
                        
                        {{-- Filter Tabs (Controller Logic) --}}
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <div class="btn-group shadow-sm" role="group">
                                <a href="{{ route('tournaments.index') }}" 
                                   class="btn {{ request('filter') === null ? 'btn-dark' : 'btn-outline-secondary' }}">
                                   All
                                </a>
                                <a href="{{ route('tournaments.index', ['filter' => 'upcoming']) }}" 
                                   class="btn {{ request('filter') === 'upcoming' ? 'btn-dark' : 'btn-outline-secondary' }}">
                                   Upcoming
                                </a>
                                <a href="{{ route('tournaments.index', ['filter' => 'registered']) }}" 
                                   class="btn {{ request('filter') === 'registered' ? 'btn-dark' : 'btn-outline-secondary' }}">
                                   My Tournaments
                                </a>
                                <a href="{{ route('tournaments.index', ['filter' => 'completed']) }}" 
                                   class="btn {{ request('filter') === 'completed' ? 'btn-dark' : 'btn-outline-secondary' }}">
                                   Completed
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. DATATABLE --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table id="tournamentsTable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 30%;">Tournament Name</th>
                                <th style="width: 20%;">Date & Time</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 20%;">Players</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tournaments as $tournament)
                            <tr>
                                <td class="align-middle text-center">{{ $loop->iteration }}</td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark" style="font-size: 1.05rem;">{{ $tournament->name }}</div>
                                    <small class="text-muted">{{ $tournament->total_rounds }} Rounds • Standard</small>
                                </td>
                                <td class="align-middle" data-sort="{{ $tournament->start_date->timestamp }}">
                                    <div class="font-weight-bold text-dark">{{ $tournament->start_date->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $tournament->start_date->format('H:i') }} WIB</small>
                                </td>
                                <td class="align-middle text-center">
                                    @if($tournament->status === 'registration')
                                        <span class="badge badge-primary px-2 py-1">Registration</span>
                                    @elseif($tournament->status === 'active')
                                        <span class="badge badge-success px-2 py-1">Live</span>
                                    @elseif($tournament->status === 'completed')
                                        <span class="badge badge-secondary px-2 py-1">Completed</span>
                                    @else
                                        <span class="badge badge-light border">{{ ucfirst($tournament->status) }}</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-2" style="height: 6px; background-color: #e9ecef;">
                                            @php
                                                $percent = ($tournament->capacity > 0) ? ($tournament->registered_player / $tournament->capacity) * 100 : 0;
                                                $color = $percent >= 100 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning' : 'bg-success');
                                            @endphp
                                            <div class="progress-bar {{ $color }}" role="progressbar" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-muted font-weight-bold text-nowrap">
                                            {{ $tournament->registered_player }}/{{ $tournament->capacity }}
                                        </small>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('tournaments.detail', ['id' => $tournament->id]) }}" class="btn btn-sm btn-outline-primary font-weight-bold">
                                        View <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Initialize DataTable --}}
@endsection
@push('scripts')
<script>
    $(function () {
        $("#tournamentsTable").DataTable({
            "responsive": true,
            "lengthChange": false, 
            "autoWidth": false,
            "searching": true,
            "pageLength": 10,
            "order": [[ 2, "desc" ]], // Sort by Date (Column Index 2)
            "language": {
                "search": "Search Name:", // Updated label
                "emptyTable": "No tournaments found."
            },
            
            // --- NEW: Restrict Search to Column 1 (Name) ---
            "columnDefs": [
                { 
                    "targets": [0, 2, 3, 4, 5], // Column Indexes: #, Date, Status, Players, Action
                    "searchable": false         // Disable search for these
                },
                {
                    "targets": [1],             // Column Index: Tournament Name
                    "searchable": true          // Enable search (Default, but good to be explicit)
                }
            ]
        });
    });
</script>
@endpush