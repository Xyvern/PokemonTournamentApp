@extends('player.layout')

@section('title', 'Dashboard')

@section('content')
    @php
        // Dummy Current Tournament (set to null if you want the "not registered" state)
        $currentTournament = (object)[
            'name' => 'Weekly Cup Vol. 12',
            'date' => now()->addDays(2),
        ];

        // Dummy Registered Tournaments
        $registeredTournaments = collect([
            (object)[
                'tournamentID' => 1,
                'name' => 'Dragon League Qualifier',
                'date' => now()->addDays(5),
                'number_of_players' => 16,
                'capacity' => 32,
                'format' => 'Standard'
            ],
            (object)[
                'tournamentID' => 2,
                'name' => 'City Championship',
                'date' => now()->addDays(10),
                'number_of_players' => 22,
                'capacity' => 32,
                'format' => 'Expanded'
            ],
        ]);

        // Dummy Upcoming Tournaments
        $tournaments = collect([
            (object)[
                'tournamentID' => 3,
                'name' => 'November Open Cup',
                'date' => now()->addDays(7),
                'number_of_players' => 10,
                'capacity' => 32,
                'format' => 'Standard'
            ],
            (object)[
                'tournamentID' => 4,
                'name' => 'PokeLeague Clash',
                'date' => now()->addDays(14),
                'number_of_players' => 18,
                'capacity' => 32,
                'format' => 'Standard'
            ],
        ]);
    @endphp
    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
        <h1 style="margin-top: 2vh">Welcome, {{ Auth::user()->nickname }}</h1>
        <h2 style="margin-top: 2vh">Your current Session</h2>
        @if (!empty($currentTournament))
            <p>You are currently registered for the following tournament:</p>
            <ul>
                <li>{{ $currentTournament->name }} - {{ $currentTournament->date }}</li>
            </ul>
        @else
            <div style="background-color: #eeeeee; padding: 1vh; border-radius: 5px; margin-top: 2vh;">
                <p style="margin: 0px">You are not currently registered for any tournaments.</p>
            </div>
        @endif
        <h2 style="margin-top: 2vh">Upcoming Registered Tournaments</h2>
        @if ($registeredTournaments->isEmpty())
            <p>No upcoming registered tournaments found.</p>
        @else
            <div class="row" style="margin-top: 2vh;">
                @foreach ($registeredTournaments as $item)
                    <div class="col-3">
                        <a href="#" style="text-decoration: none; color: inherit;">
                        {{-- <a href="{{ route('player.tournamentDetail', $item->tournamentID) }}" style="text-decoration: none; color: inherit;"> --}}
                            <div class="info-box">
                                <!-- Left side: Big date -->
                                <span class="info-box-icon bg-info d-flex flex-column justify-content-center align-items-center" style="font-size: 1.5rem;">
                                    <span style="font-size: 2rem; font-weight: bold;">{{ $item->capacity }}</span>
                                    <span style="font-size: 1rem;">Players</span>
                                </span>

                                <!-- Right side: Details -->
                                <div class="info-box-content" style="justify-content: space-between;">
                                    <span class="info-box-text" style="font-weight: bold; font-size: 1.25rem;">{{ $item->name }}</span>
                                    <span class="info-box-number" style="font-weight: 500; font-size: 1rem;">
                                        <p style="margin: 0; padding:0;">Date: {{ $item->date->format('l, d F Y, H:i') }}</p>
                                        <p style="margin: 0; padding:0;">Registered: {{ $item->number_of_players }}/{{ $item->capacity }} players</p>
                                        <p style="margin: 0; padding:0;">Format: {{ $item->format }}</p>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        <h2 style="margin-top: 2vh">Top Decks</h2>
        <div style="margin-top: 2vh;" class="row">
            <div class="col">
                <div class="card shadow-sm" style="background-color: #eeeeee">
                    <div class="row g-0 align-items-center">
                        <!-- Image -->
                        <div class="col-auto">
                            <img src="https://images.pokemontcg.io/g1/1.png" 
                                alt="Dragapult ex" 
                                style="height: 20vh; width: auto; object-fit: contain; border-radius: 8px; margin: 8px;">
                        </div>
    
                        <!-- Content -->
                        <div class="col" style="height: 20vh;">
                            <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                                <h5 class="card-title mb-2" style="font-weight:600; font-size: 2.25rem;">Dragapult ex</h5>
                                <div>
                                    <p class="card-text mb-1" style="font-size: 1.25rem;">1000 times played</p>
                                    <p class="card-text text-muted" style="font-size: 1rem;">60% win rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm" style="background-color: #eeeeee">
                    <div class="row g-0 align-items-center">
                        <!-- Image -->
                        <div class="col-auto">
                            <img src="https://images.pokemontcg.io/g1/1.png" 
                                alt="Dragapult ex" 
                                style="height: 20vh; width: auto; object-fit: contain; border-radius: 8px; margin: 8px;">
                        </div>
    
                        <!-- Content -->
                        <div class="col" style="height: 20vh;">
                            <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                                <h5 class="card-title mb-2" style="font-weight:600; font-size: 2.25rem;">Dragapult ex</h5>
                                <div>
                                    <p class="card-text mb-1" style="font-size: 1.25rem;">1000 times played</p>
                                    <p class="card-text text-muted" style="font-size: 1rem;">60% win rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm" style="background-color: #eeeeee">
                    <div class="row g-0 align-items-center">
                        <!-- Image -->
                        <div class="col-auto">
                            <img src="https://images.pokemontcg.io/g1/1.png" 
                                alt="Dragapult ex" 
                                style="height: 20vh; width: auto; object-fit: contain; border-radius: 8px; margin: 8px;">
                        </div>
    
                        <!-- Content -->
                        <div class="col" style="height: 20vh;">
                            <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                                <h5 class="card-title mb-2" style="font-weight:600; font-size: 2.25rem;">Dragapult ex</h5>
                                <div>
                                    <p class="card-text mb-1" style="font-size: 1.25rem;">1000 times played</p>
                                    <p class="card-text text-muted" style="font-size: 1rem;">60% win rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <h2 style="margin-top: 2vh">Upcoming Tournaments</h2>
        @if ($tournaments->isEmpty())
            <p>No upcoming tournaments found.</p>
        @else
            <p style="text-align: end; ">See more tournaments</p>
            <div class="row">
                @foreach ($tournaments as $item)
                    <div class="col-3">
                        <a href="#" style="text-decoration: none; color: inherit;">
                        {{-- <a href="{{ route('player.tournamentDetail', $item->tournamentID) }}" style="text-decoration: none; color: inherit;"> --}}
                            <div class="info-box">
                                <!-- Left side: Big date -->
                                <span class="info-box-icon bg-info d-flex flex-column justify-content-center align-items-center" style="font-size: 1.5rem;">
                                    <span style="font-size: 2rem; font-weight: bold;">{{ $item->capacity }}</span>
                                    <span style="font-size: 1rem;">Players</span>
                                </span>

                                <!-- Right side: Details -->
                                <div class="info-box-content" style="justify-content: space-between;">
                                    <span class="info-box-text" style="font-weight: bold; font-size: 1.25rem;">{{ $item->name }}</span>
                                    <span class="info-box-number" style="font-weight: 500; font-size: 1rem;">
                                        <p style="margin: 0; padding:0;">Date: {{ $item->date->format('l, d F Y, H:i') }}</p>
                                        <p style="margin: 0; padding:0;">Registered: {{ $item->number_of_players }}/{{ $item->capacity }} players</p>
                                        <p style="margin: 0; padding:0;">Format: {{ $item->format }}</p>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        <h2 style="margin-top: 2vh">Recent sets</h2>
        @if ($sets->isEmpty())
            <p>No recent sets found.</p>
        @else
            <a href="{{ route('sets.index') }}" style="text-align: end; display: block; margin-bottom: 1vh; text-decoration: none;">See more sets</a>
            <div class="row">
                @foreach ($sets as $set)
                    <div class="col" style="display: flex; justify-content: center;">
                        <div class="card" style="width: 100%;">
                            <div style="height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 1vh;">
                                <img src="{{ $set->images->logo }}" 
                                    alt="{{ $set->name }}" 
                                    style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                            <div class="card-body text-center" style="width: 100%;">
                                <h5 class="card-title" style="font-weight: 500;">{{ $set->name }} ({{ $set->total }} cards)</h5>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection