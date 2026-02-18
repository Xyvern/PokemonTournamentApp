@extends('player.layout')

@section('title', 'Set Detail: ' . $set->name)

@section('content')

{{-- CSS for Card Hover --}}
<style>
    .card-hover {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
        text-decoration: none !important; /* Removes blue underline from links */
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .card-hover:hover .card-body {
        background-color: #f8f9fa !important;
    }
    /* Ensure image fits nicely */
    .card-img-wrapper {
        height: 260px; /* Fixed height container */
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f1f3f5;
        border-radius: 4px;
        overflow: hidden;
    }
    .card-img-wrapper img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="container-fluid" style="width: 100%;">
        
        {{-- 1. PAGE HEADER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            {{-- Title & Logo --}}
                            <div class="col-md-8 d-flex align-items-center">
                                @if($set->images->logo)
                                    <img src="{{ $set->images->logo }}" alt="Logo" style="height: 50px; margin-right: 15px;">
                                @endif
                                <div>
                                    <h2 class="mb-0 font-weight-bold">{{ $set->name }}</h2>
                                    <p class="text-muted mb-0">Set Details & Collection</p>
                                </div>
                            </div>
                            
                            {{-- Stats / Badge --}}
                            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                <span class="badge badge-primary p-2 shadow-sm" style="font-size: 1rem;">
                                    <i class="fas fa-layer-group mr-1"></i> {{ $set->total }} Cards
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. CARDS GRID --}}
        <div class="row" id="cardList">
            @foreach($cards as $card)
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
                    <a href="{{ route('cards.detail', ['id' => $card->api_id]) }}" class="text-decoration-none text-dark">
                        <div class="card h-100 border-0 shadow-sm card-hover">
                            
                            {{-- Image Container --}}
                            <div class="card-body p-2">
                                <div class="card-img-wrapper">
                                    <img src="{{ $card->images->small }}" 
                                         alt="{{ $card->name }}" 
                                         loading="lazy">
                                </div>
                            </div>

                            {{-- Card Footer (Name) --}}
                            <div class="card-footer bg-white border-0 text-center py-2">
                                <small class="d-block font-weight-bold text-truncate" title="{{ $card->name }}">
                                    {{ $card->name }}
                                </small>
                                <small class="text-muted">{{ $card->number }} / {{ $set->printed_total }}</small>
                            </div>

                        </div>
                    </a>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection