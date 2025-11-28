@extends('player.layout')

@section('title', $card->name . ' Detail')

@section('content')

{{-- 1. STYLES --}}
<style>
    /* Page Container */
    .card-page-wrapper {
        margin: 5vh 10vw;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .card-main-title {
        text-align: center;
        margin-bottom: 4vh;
        font-weight: 800;
        font-size: 2.5rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .card-detail-grid {
        display: flex;
        flex-wrap: wrap;
        width: 100%;
        max-width: 1200px;
        gap: 2rem;
    }

    /* Left Column */
    .left-panel {
        flex: 1;
        min-width: 300px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .card-img-lg {
        width: 100%;
        max-width: 450px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .meta-data {
        margin-top: 1.5rem;
        text-align: center;
        color: #555;
        font-family: monospace;
        font-size: 1.1rem;
        background: #f8f9fa;
        padding: 10px 20px;
        border-radius: 8px;
        width: 100%;
        max-width: 450px;
    }

    /* Right Column */
    .right-panel {
        flex: 1.5;
        min-width: 300px;
    }

    /* Pokemon Specific UI */
    .hp-type-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eee;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }

    .hp-text {
        font-size: 2rem;
        font-weight: 900;
        color: #dc3545; /* HP Red */
    }

    .stage-badge {
        background-color: #333;
        color: #fff;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        text-transform: uppercase;
        margin-bottom: 2rem;
        display: inline-block;
    }

    .attack-row {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: transform 0.2s;
    }
    .attack-row:hover {
        border-color: #ccc;
    }

    .attack-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    /* Stats Grid (Weakness/Resist/Retreat) */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1rem;
        margin-top: 3rem;
        text-align: center;
        background: #f1f1f1;
        padding: 1rem;
        border-radius: 8px;
    }

    .stat-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #777;
        margin-bottom: 0.5rem;
        font-weight: bold;
    }

    /* CSS Helpers from previous context */
    .type-badge {
        display: inline-block; width: 30px; height: 30px;
        border-radius: 50%; 
        text-align: center; 
        line-height: 30px;
        color: transparent; /* Hide text since we use images now */
        font-weight: bold; font-size: 12px; margin-left: 5px;
        background-size: cover; /* Ensure image covers the circle */
        background-position: center;
        background-repeat: no-repeat;
    }
    
    .attack-cost {
        display: inline-block; width: 25px; height: 25px;
        border-radius: 50%; margin-right: 4px; vertical-align: middle;
        box-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        background-size: cover; /* Ensure image covers the circle */
        background-position: center;
        background-repeat: no-repeat;
    }
    
    /* Type Images */
    .type-grass { background-image: url('https://asia.pokemon-card.com/various_images/energy/Grass.png'); }
    .type-fire { background-image: url('https://asia.pokemon-card.com/various_images/energy/Fire.png'); }
    .type-water { background-image: url('https://asia.pokemon-card.com/various_images/energy/Water.png'); }
    .type-lightning { background-image: url('https://asia.pokemon-card.com/various_images/energy/Lightning.png'); }
    .type-psychic { background-image: url('https://asia.pokemon-card.com/various_images/energy/Psychic.png'); }
    .type-fighting { background-image: url('https://asia.pokemon-card.com/various_images/energy/Fighting.png'); }
    .type-darkness { background-image: url('https://asia.pokemon-card.com/various_images/energy/Darkness.png'); }
    .type-metal { background-image: url('https://asia.pokemon-card.com/various_images/energy/Metal.png'); }
    .type-fairy { background-image: url('https://asia.pokemon-card.com/various_images/energy/Fairy.png'); }
    .type-dragon { background-image: url('https://asia.pokemon-card.com/various_images/energy/Dragon.png'); }
    .type-colorless { background-image: url('https://asia.pokemon-card.com/various_images/energy/Colorless.png'); }

    /* Trainer Text Box */
    .trainer-box {
        border: 2px solid #e0e0e0;
        padding: 2rem;
        border-radius: 10px;
        background-color: #fff;
        font-size: 1.1rem;
        line-height: 1.6;
        font-style: italic;
        color: #444;
    }
</style>

<div class="card-page-wrapper">

    {{-- 2. MIDDLE TITLE: CARD NAME --}}
    <h1 class="card-main-title">{{ $card->name }}</h1>

    <div class="card-detail-grid">

        {{-- 3. LEFT SIDE: IMAGE + API ID + SET --}}
        <div class="left-panel">
            <img 
                src="{{ $card->images->large ?? $card->images->small }}" 
                alt="{{ $card->name }}" 
                class="card-img-lg"
            >

            <div class="meta-data">
                <div><strong>ID:</strong> {{ $card->api_id }}</div>
                <div><strong>Set:</strong> {{ $card->set->name }}</div>
                @if(isset($card->set->ptcgoCode))
                    <div class="text-muted small mt-1">({{ $card->set->ptcgoCode }})</div>
                @endif
            </div>
        </div>

        {{-- 4. RIGHT SIDE: CARD INFO --}}
        <div class="right-panel">

            @php
                $supertype = strtolower(\Illuminate\Support\Str::ascii($card->supertype));
            @endphp

            {{-- ========================================== --}}
            {{-- SCENARIO A: POKEMON --}}
            {{-- ========================================== --}}
            @if($supertype === 'pokemon')

                {{-- HP and Type --}}
                <div class="hp-type-bar">
                    <div class="hp-text">HP {{ $card->hp }}</div>
                    <div class="d-flex">
                        @foreach($card->types as $type)
                            <div class="type-badge type-{{ strtolower($type->type) }}">
                                {{ substr($type->type, 0, 1) }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Evolution Stage --}}
                <div class="stage-badge">
                    {{ $card->subtypes->first()->subtype ?? 'Basic' }}
                </div>

                {{-- Attacks --}}
                <div class="attacks-section">
                    @forelse($card->attacks as $attack)
                        <div class="attack-row">
                            <div class="attack-header">
                                <div class="d-flex align-items-center">
                                    {{-- Costs --}}
                                    <div class="me-3">
                                        @foreach($attack->costs as $cost)
                                            <span class="attack-cost type-{{ strtolower($cost->type ?? $cost->energy ?? 'colorless') }}"></span>
                                        @endforeach
                                    </div>
                                    <h4 class="m-0 fw-bold">{{ $attack->name }}</h4>
                                </div>
                                <h4 class="m-0 fw-bold">{{ $attack->damage }}</h4>
                            </div>
                            @if($attack->text)
                                <p class="text-muted m-0">{{ $attack->text }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No attacks data.</p>
                    @endforelse
                </div>

                {{-- Stats Grid --}}
                <div class="stats-grid">
                    {{-- Weakness --}}
                    <div>
                        <div class="stat-title">Weakness</div>
                        @forelse($card->weaknesses as $weakness)
                            <div class="d-flex justify-content-center align-items-center gap-1">
                                <span class="type-badge type-{{ strtolower($weakness->type) }}" style="width:20px; height:20px; line-height:20px; font-size:10px;">
                                    {{ substr($weakness->type, 0, 1) }}
                                </span>
                                <span>{{ $weakness->value }}</span>
                            </div>
                        @empty
                            <span>-</span>
                        @endforelse
                    </div>

                    {{-- Resistance --}}
                    <div>
                        <div class="stat-title">Resistance</div>
                        @if(count($card->resistances ?? []) > 0)
                            @foreach($card->resistances as $resistance)
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <span class="type-badge type-{{ strtolower($resistance->type) }}" style="width:20px; height:20px; line-height:20px; font-size:10px;">
                                        {{ substr($resistance->type, 0, 1) }}
                                    </span>
                                    <span>{{ $resistance->value }}</span>
                                </div>
                            @endforeach
                        @else
                            <span>-</span>
                        @endif
                    </div>

                    {{-- Retreat --}}
                    <div>
                        <div class="stat-title">Retreat</div>
                        @if($card->converted_retreat_cost > 0)
                            @for($i = 0; $i < $card->converted_retreat_cost; $i++)
                                <span class="attack-cost type-colorless" style="width:15px; height:15px; background-color:#aaa;"></span>
                            @endfor
                        @else
                            <span>-</span>
                        @endif
                    </div>
                </div>

            {{-- ========================================== --}}
            {{-- SCENARIO B: TRAINER --}}
            {{-- ========================================== --}}
            @elseif($supertype === 'trainer')
                
                <div class="d-flex align-items-center mb-4">
                    <span class="badge bg-secondary text-uppercase fs-6">Trainer</span>
                    <span class="ms-2 fs-5 text-muted">{{ $card->subtypes->first()->type ?? '' }}</span>
                </div>

                {{-- Text Only --}}
                <div class="trainer-box">
                    @if($card->flavor_text)
                        {{ $card->flavor_text }}
                    @elseif($card->rules)
                         {{-- Fallback if rules column exists in future --}}
                         @foreach($card->rules as $rule)
                            <p>{{ $rule->text }}</p>
                         @endforeach
                    @else
                        <p class="text-muted">No effect text available.</p>
                    @endif
                </div>

            {{-- ========================================== --}}
            {{-- SCENARIO C: ENERGY --}}
            {{-- ========================================== --}}
            @elseif($supertype === 'energy')

                {{-- Display Image Only (Logic: Right side is empty or minimal as image is on left) --}}
                <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                    {{-- Leaving mostly empty to focus on the image on the left --}}
                    <div>
                        <span class="badge bg-info text-dark">Energy Card</span>
                    </div>
                </div>

            @endif

        </div>
    </div>
</div>
@endsection