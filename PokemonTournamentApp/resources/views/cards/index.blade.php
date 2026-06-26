@extends('player.layout')

@section('title', 'Cards Collection')

@section('content')
<style>
    .card-hover {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
        border-radius: 8px;
    }
    .card-hover:hover {
        transform: scale(1.08);
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        z-index: 10;
        position: relative;
    }
    .set-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 10px;
        margin-top: 10px;
        margin-bottom: 20px;
    }
</style>

<div class="responsive-container">

    {{-- 1. Page Header & Search Bar --}}
    <div class="row mb-4 align-items-center">
        {{-- Left: Titles --}}
        <div class="col-md-6 mb-3 mb-md-0">
            <h2 class="mb-1 font-weight-bold">Cards Collection</h2>
            <p class="text-muted mb-0">Browse through the latest Pokémon TCG expansions.</p>
        </div>

        {{-- Right: Search Bar --}}
        <div class="col-md-6">
            <form action="{{ url()->current() }}" method="GET" class="m-0">
                <div class="input-group shadow-sm">
                    <input type="text" name="search" class="form-control border-light" placeholder="Search for a Pokémon..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary" title="Clear Search">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Card Grid (Grouped by Set) --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row">
                @forelse($sets as $set)
                    
                    {{-- The Set Heading --}}
                    <div class="col-12 set-header {{ $loop->first ? 'mt-2' : 'mt-5' }}">
                        <div class="d-flex align-items-center justify-content-between">
                            <h4 class="font-weight-bold mb-0 text-dark">
                                {{ $set->name }}
                            </h4>
                            <span class="badge badge-secondary px-3 py-2">
                                Released: {{ \Carbon\Carbon::parse($set->release_date)->format('M d, Y') }} 
                                • {{ $set->cards->count() }} Cards
                            </span>
                        </div>
                    </div>

                    {{-- The Cards for this Set --}}
                    @forelse($set->cards as $card)
                        <div class="col-xl-1 col-lg-2 col-md-3 col-sm-4 col-4 mb-4 text-center">
                            {{-- Make sure this route matches your web.php --}}
                            <a href="{{ route('cards.detail', ['id' => $card->api_id]) }}" class="d-block text-decoration-none">
                                @php
                                    $imgSrc = $card->images->small ?? 'https://images.pokemontcg.io/'.str_replace('-', '/', $card->api_id).'.png';
                                @endphp
                                
                                <img src="{{ $imgSrc }}" 
                                     alt="{{ $card->name }}" 
                                     class="img-fluid card-hover mb-2"
                                     loading="lazy"
                                     style="width: 100%; max-width: 140px;">
                                
                                <div class="text-truncate font-weight-bold text-dark" style="font-size: 0.8rem;" title="{{ $card->name }}">
                                    {{ $card->name }}
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $card->api_id }}</small>
                            </a>
                        </div>
                    @empty
                        <div class="col-12 text-center py-3">
                            <p class="text-muted mb-0">No cards found for this set yet.</p>
                        </div>
                    @endforelse

                @empty
                    <div class="col-12 text-center py-5">
                        <h5 class="text-muted">No sets available.</h5>
                    </div>
                @endforelse
            </div>
        </div>
        
        {{-- 3. Laravel Backend Pagination Links --}}
        @if($sets->hasPages())
            <div class="card-footer bg-white border-top-0 pt-3 pb-3">
                <div class="d-flex justify-content-center">
                    {{ $sets->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection