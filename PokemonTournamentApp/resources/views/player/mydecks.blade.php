@extends('player.layout')

@section('title', 'My Decks')

@section('content')

{{-- Custom CSS for Deck Cards --}}
<style>
    .deck-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .deck-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .card-img-container {
        height: 220px;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .card-img-top {
        height: 100%;
        width: 100%;
        object-fit: contain;
    }
    .deck-link {
        text-decoration: none; 
        color: inherit;
    }
    .deck-link:hover {
        text-decoration: none;
        color: inherit;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. PAGE HEADER & ACTIONS --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-end">
                        {{-- Title Section --}}
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">My Decks</h2>
                            <p class="text-muted mb-0">Manage and view your card collection</p>
                        </div>
                        
                        {{-- Action Section (Create & Search) --}}
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <div class="d-flex flex-column flex-md-row justify-content-end align-items-center gap-3">
                                
                                {{-- Create Button --}}
                                <a href="{{ route('player.createDeck') }}" class="btn btn-primary shadow-sm font-weight-bold mr-md-3 mb-2 mb-md-0">
                                    <i class="fas fa-plus mr-1"></i> Create New Deck
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. DECK GRID --}}
    <div class="row">
        <div class="col-12">
            
            @if($decks->isEmpty())
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-layer-group fa-3x mb-3 text-gray-300"></i>
                            <h4 class="text-muted">No Decks Found</h4>
                            <p class="mb-3">You haven't built any decks yet.</p>
                            <a href="{{ route('player.createDeck') }}" class="btn btn-outline-primary">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="row"> 
                    @foreach($decks as $deck)
                        @php
                            // Thumbnail Logic
                            $thumbnail = 'https://asia.pokemon-card.com/id/card-img/products/Back%20of%20card.png';
                            if ($deck->globaldeck && $deck->globaldeck->archetype_id && $deck->globaldeck->archetype?->keyCard?->images?->small) {
                                $thumbnail = $deck->globaldeck->archetype->keyCard->images->small;
                            }
                        @endphp

                        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
                            <a href="{{ route('showDeck', $deck->id) }}" class="deck-link">
                                <div class="card deck-card h-100">
                                    {{-- Image --}}
                                    <div class="card-img-container">
                                        <img src="{{ $thumbnail }}" alt="{{ $deck->name }}" class="card-img-top" loading="lazy">
                                    </div>

                                    {{-- Content --}}
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <h5 class="card-title font-weight-bold text-dark mb-1 text-truncate" title="{{ $deck->name }}">
                                            {{ $deck->name }}
                                        </h5>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-2">
                                            <small class="text-muted">
                                                <i class="far fa-clock mr-1"></i> {{ $deck->created_at->diffForHumans() }}
                                            </small>
                                            
                                            {{-- Optional: Add Archetype Badge here if available --}}
                                            @if($deck->globaldeck && $deck->globaldeck->archetype)
                                                <span class="badge badge-light border text-truncate" style="max-width: 100px;">
                                                    {{ $deck->globaldeck->archetype->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination (Optional) --}}
                @if(method_exists($decks, 'links'))
                    <div class="d-flex justify-content-end mt-3">
                        {{ $decks->links() }}
                    </div>
                @endif

            @endif
        </div>
    </div>

</div>
@endsection