@extends('player.layout')

@section('content')

<style>
    /* Card Hover Effect */
    .deck-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden; /* Ensures image zoom or corners stay clean */
    }
    
    .deck-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    /* Thumbnail Container */
    .card-img-container {
        height: 220px; /* Fixed height for consistency */
        background-color: #f8f9fa; /* Light gray background for transparent images */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .card-img-top {
        height: 100%;
        width: 100%;
        object-fit: contain; /* Ensures the whole card is visible */
    }

    /* Link styling wrapper */
    .deck-link {
        text-decoration: none; 
        color: inherit;
    }
    .deck-link:hover {
        text-decoration: none;
        color: inherit;
    }
</style>

<!-- Mandatory Container -->
<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 fw-bold">Your Decks</h3>
            <span class="text-muted small">Manage and view your collection</span>
        </div>

        <a href="{{ route('player.createDeck') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus"></i> Create New Deck
        </a>
    </div>

    @if($decks->isEmpty())
        <div class="alert alert-light border shadow-sm text-center p-5">
            <h4 class="text-muted">No Decks Found</h4>
            <p class="mb-3">You haven't built any decks yet.</p>
            <a href="{{ route('player.createDeck') }}" class="btn btn-outline-primary">
                Get Started
            </a>
        </div>
    @else

        <div class="row g-4"> <!-- g-4 adds gutter spacing -->

            @foreach($decks as $deck)
                @php
                    // Thumbnail Logic
                    $thumbnail = 'https://asia.pokemon-card.com/id/card-img/products/Back%20of%20card.png';
                    
                    // Check if globaldeck exists, has archetype_id, and has the nested image
                    if (
                        $deck->globaldeck && 
                        $deck->globaldeck->archetype_id && 
                        $deck->globaldeck->archetype?->keyCard?->images?->small
                    ) {
                        $thumbnail = $deck->globaldeck->archetype->keyCard->images->small;
                    }
                @endphp

                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                    
                    <a href="{{ route('player.showDeck', $deck->id) }}" class="deck-link">
                        <div class="card deck-card h-100">
                            
                            <!-- Image Section -->
                            <div class="card-img-container">
                                <img src="{{ $thumbnail }}" alt="{{ $deck->name }}" class="card-img-top" loading="lazy">
                            </div>

                            <div class="card-body" style="display:flex; flex-direction: column; justify-content: space-between;">
                                <h5 class="card-title font-weight-bold text-dark mb-1">
                                    {{ $deck->name }}
                                </h5>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        <i class="far fa-clock"></i> {{ $deck->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </a>

                </div>
            @endforeach

        </div>

    @endif

</div>

@endsection