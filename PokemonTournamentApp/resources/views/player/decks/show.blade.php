@extends('player.layout')
<style>
/* 1. The Grid Layout */
.card-grid {
    display: grid;
    /* Adjust '110px' to change card size. 110px fits standard screens well. */
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 15px;
}

/* 2. The Card Item */
.card-wrapper {
    position: relative;
    transition: transform 0.2s;
    cursor: pointer;
}

.card-wrapper:hover {
    transform: scale(1.05);
    z-index: 5;
}

.deck-card-img {
    width: 100%;
    height: auto;
    border-radius: 6px;
    /* Optional: Slight shadow to make cards pop off the gray bg */
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

/* 3. The Red Hexagon Badge */
.qty-badge {
    position: absolute;
    bottom: -10px; /* Pulls it slightly off the bottom edge */
    left: 50%;
    transform: translateX(-50%); /* Centers it horizontally */
    
    width: 32px;
    height: 32px;
    
    background-color: #d9243d; /* The specific red from your image */
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
    
    display: flex;
    align-items: center;
    justify-content: center;
    
    /* Creates the Hexagon Shape */
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
    
    /* Adds a slight shadow filter (optional, as regular box-shadow won't work well with clip-path) */
    filter: drop-shadow(0px 2px 2px rgba(0,0,0,0.5));
}
</style>

@section('title', 'Deck Details')

@section('content')

@php
    // Define sort order
    $typeOrder = ['Pokémon' => 1, 'Trainer' => 2, 'Energy' => 3];
    
    // Sort cards so they flow logically, but stay in one list
    $sortedCards = $deck->globalDeck->cards->sortBy(function($card) use ($typeOrder) {
        // Get the order value, default to 99 if unknown
        return $typeOrder[$card->supertype] ?? 99;
    });
@endphp

<div class="responsive-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-dark font-weight-bold mb-0">{{ $deck->name }}</h3>
        @auth
        <button onclick="copyDeckAndRedirect('{{ $deck->globalDeck->deck_hash }}', '{{ route('player.createDeck', ['copy' => $deck->globalDeck->deck_hash]) }}')" class="btn btn-primary font-weight-bold px-4 shadow-sm">
            <i class="fas fa-copy mr-2"></i> Copy Deck
        </button>
        @endauth
    </div>

    <div class="p-4 rounded shadow-sm" style="background-color: #2b2b2b;">
        <div class="card-grid">
            @foreach($sortedCards as $card)
                <div class="card-wrapper" title="{{ $card->name }}">
                    <a href="{{ route('cards.detail', $card->api_id) }}">
                        <img src="{{ $card->images->small ?? '' }}" alt="{{ $card->name }}" class="deck-card-img">
                        
                        <div class="qty-badge">
                            {{ $card->pivot->quantity }}
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    @if ($deckHistory->decks->isNotEmpty())
        <h4>Deck List Played by</h4>
        <ul>
            @foreach ($deckHistory->decks as $deck)
                @foreach ($deck->tournamentEntries->whereNotNull('rank') as $entry)
                    @if ($entry->tournament->status == "completed")   
                        <li>
                            {{ \App\Helpers\NumberHelper::ordinal($entry->rank) }} Place {{ $entry->tournament->name }}, {{ $entry->user->nickname }}
                        </li>
                    @endif
                @endforeach
            @endforeach
        </ul>
    @endif
</div>

@push('scripts')
<script>
function copyDeckAndRedirect(hash, redirectUrl) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(hash).then(function() {
            $(document).Toasts('create', {
                class: 'bg-success',
                title: 'Copied',
                icon: 'fas fa-copy fa-lg',
                autohide: true,
                delay: 2000,
                body: 'Deck hash copied to clipboard!'
            });
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 1000);
        }).catch(function(err) {
            window.location.href = redirectUrl;
        });
    } else {
        window.location.href = redirectUrl;
    }
}
</script>
@endpush

@endsection