@extends('player.layout')

@section('title', 'Dashboard')

@section('content')

    {{-- ADDED: Animation Style --}}
    <style>
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important;
        }
    </style>

    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
        <h2 style="margin-top: 2vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">Your current Session</h2>
        @if (!empty($currentTournaments))
            <div class="row" style="margin-top: 2vh;">    
                @foreach ($currentTournaments as $item)
                    @php
                        // Get the current user's entry for this specific tournament
                        $myEntry = $item->entries->where('user_id', Auth::id())->first();
                    @endphp
                    <div class="col-12">
                        <a href="{{ route('tournaments.detail', ['id' => $item->id]) }}" style="text-decoration: none; color: inherit;">
                            {{-- ADDED: hover-lift class --}}
                            <div class="info-box shadow-sm mb-3 hover-lift">
                                <span class="info-box-icon bg-info d-flex flex-column justify-content-center align-items-center" style="font-size: 1.5rem; min-width: 80px;">
                                    <span style="font-size: 1.5rem; font-weight: bold;">{{ $item->capacity }}</span>
                                    <span style="font-size: 0.8rem;">Max</span>
                                </span>

                                <div class="info-box-content p-2">
                                    <span class="info-box-text" style="font-weight: bold; font-size: 1.1rem;">{{ $item->name }}</span>
                                    <div class="info-box-number text-muted" style="font-weight: 500; font-size: 0.9rem;">
                                        <p class="mb-1"><i class="fas fa-calendar-alt mr-1"></i> {{ $item->start_date instanceof \DateTime ? $item->start_date->format('d M Y') : $item->start_date }}</p>
                                        <p class="mb-0"><i class="fas fa-users mr-1"></i> {{ $item->registered_player }}/{{ $item->capacity }} players</p>
                                    </div>
                                </div>
                                <div class="info-box-content p-2 text-right">
                                    <span class="d-block text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">My Performance</span>
                                    
                                    <div class="d-flex align-items-end justify-content-end mb-1">
                                        <span style="font-size: 1.3rem; font-weight: bold; line-height: 1;" class="text-primary mr-1">
                                            {{ $myEntry->points }}
                                        </span>
                                        <span style="font-size: 0.8rem; color: #6c757d; margin-bottom: 2px;">Pts</span>
                                    </div>

                                    <div style="font-size: 0.85rem;">
                                        <span class="badge badge-success" title="Wins">{{ $myEntry->wins }}W</span>
                                        <span class="badge badge-danger" title="Losses">{{ $myEntry->losses }}L</span>
                                        <span class="badge badge-secondary" title="Ties">{{ $myEntry->ties }}T</span>
                                    </div>

                                    @if($myEntry->rank)
                                    <div class="mt-1" style="font-size: 0.8rem; font-weight: bold; color: #495057;">
                                        Rank: #{{ $myEntry->rank }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div style="background-color: #eeeeee; padding: 1vh; border-radius: 5px; margin-top: 2vh;">
                <p style="margin: 0px">You are not currently registered for any tournaments.</p>
            </div>
        @endif
        <h2 style="margin-top: 2vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">Upcoming Registered Tournaments</h2>
        @if (empty($registeredTournaments))
            <p>No upcoming registered tournaments found.</p>
        @else
            <div class="row" style="margin-top: 2vh;">
                @foreach ($registeredTournaments as $item)
                    <div class="col-3">
                        <a href="{{ route('tournaments.detail', ['id' => $item->id]) }}" style="text-decoration: none; color: inherit;">
                            {{-- ADDED: hover-lift class --}}
                            <div class="info-box shadow-sm mb-3 hover-lift">
                                <span class="info-box-icon bg-info d-flex flex-column justify-content-center align-items-center" style="font-size: 1.5rem; min-width: 80px;">
                                    <span style="font-size: 1.5rem; font-weight: bold;">{{ $item->capacity }}</span>
                                    <span style="font-size: 0.8rem;">Max</span>
                                </span>

                                <div class="info-box-content p-2">
                                    <span class="info-box-text" style="font-weight: bold; font-size: 1.1rem;">{{ $item->name }}</span>
                                    <div class="info-box-number text-muted" style="font-weight: 500; font-size: 0.9rem;">
                                        <p class="mb-1"><i class="fas fa-calendar-alt mr-1"></i> {{ $item->start_date instanceof \DateTime ? $item->start_date->format('d M Y') : $item->start_date }}</p>
                                        <p class="mb-0"><i class="fas fa-users mr-1"></i> {{ $item->registered_player }}/{{ $item->capacity }} players</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="text-right mt-2">
            <a href="{{ route('tournaments.index', ['filter' => 'registered']) }}" class="btn btn-sm btn-outline-info">See more upcoming registered tournaments &rarr;</a>
        </div>
        <h2 style="margin-top: 2vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">Top Archetypes</h2>

        <div class="row" style="margin-top: 2vh;">
            @foreach ($archetypes as $archetype)
                <div class="col-lg-3 col-6">
                    <a href="{{ route('archetypes.detail', ['id' => $archetype->id]) }}" style="text-decoration: none; color: inherit;">
                        {{-- ADDED: hover-lift class --}}
                        <div class="info-box shadow-sm mb-3 align-items-center hover-lift" style="min-height: 100px;">
                            <div class="bg-light elevation-1 d-flex justify-content-center align-items-center rounded-left overflow-hidden" 
                                style="width: 144px; height: 200px; flex-shrink: 0;">
                                <img src="{{ $archetype->keyCard->images->small }}" 
                                    alt="{{ $archetype->name }}" 
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            </div>

                            <div class="info-box-content p-2 pl-3">
                                <span class="info-box-text font-weight-bold" style="font-size: 1.1rem; line-height: 1.2; white-space: normal;">
                                    {{ $archetype->name }}
                                </span>
                                
                                <div class="info-box-number text-muted mt-1" style="font-size: 0.9rem;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-gamepad mr-1 text-primary"></i> 
                                            {{ $archetype->times_played }} Played
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span>
                                            <i class="fas fa-trophy mr-1 text-warning"></i> 
                                            {{ $archetype->win_rate }}% WR
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="text-right mt-2">
            <a href="{{ route('archetypes.index') }}" class="btn btn-sm btn-outline-info">See more archetypes &rarr;</a>
        </div>
        {{-- ------------------ --}}
        <div class="row">
            <div class="col-md-6">
                <h2 style="margin-top: 2vh; border-bottom: 2px solid #dee2e6; padding-bottom: 10px;">Recent Tournaments</h2>
                
                @if ($recentTournaments->isEmpty())
                    <div class="alert alert-light" style="margin-top: 2vh;">No recent tournaments found.</div>
                @else
                    <div class="d-flex flex-column gap-3" style="margin-top: 2vh;">
                        @foreach ($recentTournaments->take(3) as $item)
                            <a href="{{ route('tournaments.detail', ['id' => $item->id]) }}" style="text-decoration: none; color: inherit;">
                                {{-- ADDED: hover-lift class --}}
                                <div class="info-box shadow-sm mb-3 hover-lift">
                                    <span class="info-box-icon bg-secondary d-flex flex-column justify-content-center align-items-center" style="font-size: 1.5rem; min-width: 80px;">
                                        <span style="font-size: 1.5rem; font-weight: bold;">{{ $item->capacity }}</span>
                                        <span style="font-size: 0.8rem;">Max</span>
                                    </span>

                                    <div class="info-box-content p-2">
                                        <span class="info-box-text" style="font-weight: bold; font-size: 1.1rem;">{{ $item->name }}</span>
                                        <div class="info-box-number text-muted" style="font-weight: 500; font-size: 0.9rem;">
                                            <p class="mb-1"><i class="fas fa-calendar-alt mr-1"></i> {{ $item->start_date instanceof \DateTime ? $item->start_date->format('d M Y') : $item->start_date }}</p>
                                            <p class="mb-0"><i class="fas fa-users mr-1"></i> {{ $item->registered_player }}/{{ $item->capacity }} players</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    
                    <div class="text-right mt-2">
                        <a href="{{ route('tournaments.index', ['filter' => 'completed']) }}" class="btn btn-sm btn-outline-secondary">See all recent tournaments &rarr;</a>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <h2 style="margin-top: 2vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">Upcoming Tournaments</h2>

                @if ($upcomingTournaments->isEmpty())
                    <div class="alert alert-light" style="margin-top: 2vh;">No upcoming tournaments found.</div>
                @else
                    <div class="d-flex flex-column gap-3" style="margin-top: 2vh;">
                        @foreach ($upcomingTournaments->take(3) as $item)
                            <a href="{{ route('tournaments.detail', ['id' => $item->id]) }}" style="text-decoration: none; color: inherit;">
                                {{-- ADDED: hover-lift class --}}
                                <div class="info-box shadow-sm mb-3 hover-lift">
                                    <span class="info-box-icon bg-info d-flex flex-column justify-content-center align-items-center" style="font-size: 1.5rem; min-width: 80px;">
                                        <span style="font-size: 1.5rem; font-weight: bold;">{{ $item->capacity }}</span>
                                        <span style="font-size: 0.8rem;">Max</span>
                                    </span>

                                    <div class="info-box-content p-2">
                                        <span class="info-box-text" style="font-weight: bold; font-size: 1.1rem;">{{ $item->name }}</span>
                                        <div class="info-box-number text-muted" style="font-weight: 500; font-size: 0.9rem;">
                                            <p class="mb-1"><i class="fas fa-calendar-alt mr-1"></i> {{ $item->start_date instanceof \DateTime ? $item->start_date->format('d M Y') : $item->start_date }}</p>
                                            <p class="mb-0"><i class="fas fa-users mr-1"></i> {{ $item->registered_player }}/{{ $item->capacity }} players</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="text-right mt-2">
                        <a href="{{ route('tournaments.index', ['filter' => 'upcoming']) }}" class="btn btn-sm btn-outline-info">See all upcoming tournaments &rarr;</a>
                    </div>
                @endif
            </div>
        </div>
        {{-- ---------------------------------- --}}
        <h2 style="margin-top: 2vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">Recent sets</h2>
        @if ($sets->isEmpty())
            <p>No recent sets found.</p>
        @else
        <div class="row" style="margin-top: 2vh;">
            @foreach ($sets as $set)
            <div class="col" style="display: flex; justify-content: center;">
                {{-- ADDED: hover-lift class --}}
                <div class="card hover-lift" style="width: 100%;">
                    <a href=" {{ route('sets.detail', $set->id) }}" style="text-decoration: none; color: inherit;">
                        <div style="height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 1vh;">
                            <img src="{{ $set->images->logo }}" 
                            alt="{{ $set->name }}" 
                            style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                        <div class="card-body text-center" style="width: 100%;">
                            <h5 class="card-title" style="font-weight: 500;">{{ $set->name }} ({{ $set->total }} cards)</h5>
                        </div>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-right mt-2">
            <a href="{{ route('sets.index') }}" class="btn btn-sm btn-outline-info">See more sets &rarr;</a>
        </div>
        @endif
    </div>
@endsection