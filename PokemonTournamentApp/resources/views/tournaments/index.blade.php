@extends('player.layout')

@section('title', 'All Tournaments')

@section('content')
<div class="container-fluid">
    
    {{-- 1. PAGE HEADER & FILTERS --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <h2 class="mb-0">All Tournaments</h2>
                            <p class="text-muted">Find and register for upcoming events</p>
                        </div>
                        
                        {{-- Search & Filter Form --}}
                        <div class="col-md-6">
                            <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2 justify-content-md-end">
                                {{-- Status Filter --}}
                                <div class="form-group mb-0 mr-2">
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="">All Statuses</option>
                                        <option value="registration" {{ request('status') == 'registration' ? 'selected' : '' }}>Registration Open</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Live / Active</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                </div>

                                {{-- Search Bar --}}
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search tournament name..." value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. TOURNAMENTS TABLE --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 35%;">Tournament Name</th>
                                    <th style="width: 20%;">Date & Time</th>
                                    <th style="width: 15%;">Status</th>
                                    <th style="width: 15%;">Players</th>
                                    <th style="width: 10%; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tournaments as $tournament)
                                <tr>
                                    <td>{{ $loop->iteration + ($tournaments->currentPage() - 1) * $tournaments->perPage() }}</td>
                                    <td>
                                        <div class="font-weight-bold text-dark" style="font-size: 1.05rem;">{{ $tournament->name }}</div>
                                        <small class="text-muted">{{ $tournament->total_rounds }} Rounds • Standard Format</small>
                                    </td>
                                    <td>
                                        <div>{{ $tournament->start_date->format('d M Y') }}</div>
                                        <small class="text-muted">{{ $tournament->start_date->format('H:i') }}</small>
                                    </td>
                                    <td>
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
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 mr-2" style="height: 6px;">
                                                @php
                                                    $percent = ($tournament->capacity > 0) ? ($tournament->registered_player / $tournament->capacity) * 100 : 0;
                                                    $color = $percent >= 100 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $color }}" role="progressbar" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <small class="text-muted text-nowrap">
                                                {{ $tournament->registered_player }}/{{ $tournament->capacity }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        {{-- Explicitly passing 'id' parameter to match your route definition --}}
                                        <a href="{{ route('tournaments.detail', ['id' => $tournament->id]) }}" class="btn btn-sm btn-outline-primary">
                                            Details <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-search fa-2x mb-3"></i>
                                            <p class="mb-0">No tournaments found matching your criteria.</p>
                                            @if(request('search') || request('status'))
                                                <a href="{{ url()->current() }}" class="btn btn-link btn-sm mt-2">Clear Filters</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- Pagination Links --}}
                @if($tournaments->hasPages())
                    <div class="card-footer bg-white d-flex justify-content-end">
                        {{ $tournaments->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection