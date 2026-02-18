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
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    /* MAIN TITLE */
    .card-main-title {
        text-align: center;
        margin-bottom: 3rem;
        font-weight: 700;
        font-size: 2.25rem;
        color: #2c3e50;
        letter-spacing: -0.5px;
    }

    .card-detail-grid {
        display: flex;
        flex-wrap: wrap;
        width: 100%;
        max-width: 1200px;
        gap: 3rem;
    }

    /* Left Column */
    .left-panel {
        flex: 1;
        min-width: 320px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .card-img-lg {
        width: 100%;
        max-width: 420px;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .card-img-lg:hover { transform: scale(1.02); }

    .meta-data {
        margin-top: 2rem;
        width: 100%;
        max-width: 420px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .meta-row {
        display: flex; justify-content: space-between; padding: 0.5rem 0;
        border-bottom: 1px solid #f1f3f5; font-size: 0.95rem;
    }
    .meta-row:last-child { border-bottom: none; }
    .meta-label { color: #868e96; font-weight: 600; }
    .meta-value { color: #212529; font-weight: 500; }

    /* Right Column */
    .right-panel { flex: 1.5; min-width: 320px; }

    /* Pokemon Specific UI */
    .hp-type-bar {
        display: flex; justify-content: space-between; align-items: flex-end;
        border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1.5rem;
    }
    .hp-text { font-size: 1.75rem; font-weight: 700; color: #e03131; line-height: 1; }
    .hp-label { font-size: 1rem; color: #868e96; margin-right: 4px; font-weight: 600; }

    .stage-badge {
        background-color: #343a40; color: #fff; padding: 6px 16px;
        border-radius: 6px; font-size: 0.85rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2rem;
        display: inline-block;
    }

    /* --- NEW: ABILITY SECTION --- */
    .ability-row {
        background: #fff0f0; /* Light red tint for Ability */
        border: 1px solid #ffc9c9;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .ability-name {
        color: #c92a2a; /* Dark Red */
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }
    .ability-badge {
        background-color: #c92a2a;
        color: white;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 4px;
        margin-right: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Attack Rows */
    .attack-row {
        background: #fff; border-bottom: 1px solid #f1f3f5; padding: 1.5rem 0;
    }
    .attack-row:last-child { border-bottom: none; }
    .attack-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .attack-name { font-size: 1.2rem; font-weight: 700; color: #212529; }
    .attack-damage { font-size: 1.25rem; font-weight: 700; color: #212529; }
    .attack-text { font-size: 0.95rem; color: #495057; line-height: 1.5; margin-top: 0.5rem; }

    /* Stats Grid */
    .stats-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;
        background: #dee2e6; border-radius: 8px; overflow: hidden; margin-top: 3rem;
    }
    .stat-box { background: #f8f9fa; padding: 1rem; text-align: center; }
    .stat-title {
        font-size: 0.75rem; text-transform: uppercase; color: #adb5bd;
        font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.5rem;
    }

    /* Icons */
    .type-badge {
        display: inline-block; width: 32px; height: 32px; border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-left: 8px;
        background-size: cover; background-position: center;
    }
    .attack-cost {
        display: inline-block; width: 24px; height: 24px; border-radius: 50%;
        margin-right: 6px; vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        background-size: cover; background-position: center;
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

    /* Trainer Box */
    .trainer-box {
        background: #f8f9fa; border-left: 4px solid #ced4da; padding: 1.5rem;
        border-radius: 4px; font-size: 1rem; line-height: 1.6; color: #343a40;
    }
</style>

<div class="card-page-wrapper">

    {{-- TITLE --}}
    <h1 class="card-main-title">{{ $card->name }}</h1>

    <div class="card-detail-grid">
        {{-- LEFT SIDE --}}
        <div class="left-panel">
            <img src="{{ $card->images->large ?? $card->images->small }}" alt="{{ $card->name }}" class="card-img-lg">
            <div class="meta-data">
                <div class="meta-row"><span class="meta-label">ID</span><span class="meta-value">{{ $card->api_id }}</span></div>
                <div class="meta-row"><span class="meta-label">Set</span><span class="meta-value">{{ $card->set->name }}</span></div>
                <div class="meta-row"><span class="meta-label">Number</span><span class="meta-value">{{ $card->number }} / {{ $card->set->printed_total }}</span></div>
                <div class="meta-row"><span class="meta-label">Artist</span><span class="meta-value">{{ $card->artist ?? 'Unknown' }}</span></div>
            </div>
        </div>

        {{-- RIGHT SIDE --}}
        <div class="right-panel">
            @php $supertype = strtolower(\Illuminate\Support\Str::ascii($card->supertype)); @endphp

            @if($supertype === 'pokemon')
                {{-- HP & Type --}}
                <div class="hp-type-bar">
                    <div><span class="hp-label">HP</span><span class="hp-text">{{ $card->hp }}</span></div>
                    <div class="d-flex">
                        @foreach($card->types as $type)
                            <div class="type-badge type-{{ strtolower($type->type) }}"></div>
                        @endforeach
                    </div>
                </div>

                {{-- Stage --}}
                <div class="stage-badge">{{ $card->subtypes->first()->subtype ?? 'Basic Pokémon' }}</div>

                {{-- 1. NEW: ABILITIES SECTION --}}
                @if(isset($card->abilities) && count($card->abilities) > 0)
                    <div class="abilities-section">
                        @foreach($card->abilities as $ability)
                            <div class="ability-row">
                                <div class="ability-name">
                                    <span class="ability-badge">{{ $ability->type ?? 'Ability' }}</span>
                                    {{ $ability->name }}
                                </div>
                                <p class="mb-0 text-dark">{{ $ability->text }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- 2. ATTACKS SECTION --}}
                <div class="attacks-section">
                    @forelse($card->attacks as $attack)
                        <div class="attack-row">
                            <div class="attack-header">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="min-width: 80px;">
                                        @foreach($attack->costs as $cost)
                                            <span class="attack-cost type-{{ strtolower($cost->cost ?? 'colorless') }}"></span>
                                        @endforeach
                                    </div>
                                    <span class="attack-name">{{ $attack->name }}</span>
                                </div>
                                <span class="attack-damage">{{ $attack->damage }}</span>
                            </div>
                            @if($attack->text)
                                <p class="attack-text">{{ $attack->text }}</p>
                            @endif
                        </div>
                    @empty
                        {{-- Only show 'No attacks' if there are also no abilities, to avoid empty looking cards --}}
                        @if(!isset($card->abilities) || count($card->abilities) === 0)
                            <p class="text-muted">No attacks available.</p>
                        @endif
                    @endforelse
                </div>

                {{-- 3. STATS GRID --}}
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-title">Weakness</div>
                        @forelse($card->weaknesses as $weakness)
                            <div class="d-flex justify-content-center align-items-center gap-1">
                                <span class="type-badge type-{{ strtolower($weakness->type) }}" style="width:20px; height:20px; margin:0;"></span>
                                <span class="fw-bold ms-1" style="color:#495057;">{{ $weakness->value }}</span>
                            </div>
                        @empty <span class="text-muted">-</span> @endforelse
                    </div>
                    <div class="stat-box">
                        <div class="stat-title">Resistance</div>
                        @if(count($card->resistances ?? []) > 0)
                            @foreach($card->resistances as $resistance)
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <span class="type-badge type-{{ strtolower($resistance->type) }}" style="width:20px; height:20px; margin:0;"></span>
                                    <span class="fw-bold ms-1" style="color:#495057;">{{ $resistance->value }}</span>
                                </div>
                            @endforeach
                        @else <span class="text-muted">-</span> @endif
                    </div>
                    <div class="stat-box">
                        <div class="stat-title">Retreat</div>
                        @if($card->converted_retreat_cost > 0)
                            <div class="d-flex justify-content-center">
                                @for($i = 0; $i < $card->converted_retreat_cost; $i++)
                                    <span class="attack-cost type-colorless" style="width:18px; height:18px; background-color:#adb5bd; margin:0 2px;"></span>
                                @endfor
                            </div>
                        @else <span class="text-muted">-</span> @endif
                    </div>
                </div>

            @elseif($supertype === 'trainer')
                {{-- TRAINER LOGIC (Same as before) --}}
                <div class="d-flex align-items-center mb-4">
                    <span class="stage-badge mb-0" style="background-color: #495057;">Trainer</span>
                    <span class="ms-3 fs-5 fw-bold text-dark">{{ $card->subtypes->first()->type ?? '' }}</span>
                </div>
                <div class="trainer-box">
                    @if($card->flavor_text) <div class="mb-3 fst-italic text-muted">{{ $card->flavor_text }}</div> @endif
                    @if($card->rules)
                        @foreach($card->rules as $rule) <p class="mb-2">{{ $rule->text }}</p> @endforeach
                    @else <p class="text-muted">No effect text available.</p> @endif
                </div>

            @elseif($supertype === 'energy')
                {{-- ENERGY LOGIC (Same as before) --}}
                @php $isBasic = \Illuminate\Support\Str::contains(strtolower($card->subtypes->first()->type ?? 'Energy'), 'basic'); @endphp
                <div class="d-flex align-items-center mb-4">
                    <span class="stage-badge mb-0 {{ $isBasic ? 'bg-info' : 'bg-warning text-dark' }}">
                        {{ $card->subtypes->first()->type ?? 'Energy' }}
                    </span>
                </div>
                @if(!$isBasic)
                    <div class="trainer-box">
                        @if(isset($card->rules) && count($card->rules) > 0)
                            @foreach($card->rules as $rule) <p>{{ is_string($rule) ? $rule : ($rule->text ?? '') }}</p> @endforeach
                        @else <p class="text-muted">Special Energy card.</p> @endif
                    </div>
                @endif
            @endif

        </div>
    </div>
</div>
@endsection