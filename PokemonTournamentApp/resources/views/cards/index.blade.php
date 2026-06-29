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

    <div class="row mb-4 align-items-center">
        {{-- Left: Titles --}}
        <div class="col-md-12 mb-3">
            <h2 class="mb-1 font-weight-bold">Cards Collection</h2>
            <p class="text-muted mb-0">Browse through the latest Pokémon TCG expansions.</p>
        </div>

        {{-- Filters --}}
        <div class="col-md-12">
            <form action="{{ url()->current() }}" method="GET" class="m-0">
                <div class="row">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control shadow-sm" placeholder="Search for a Pokémon..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <select name="supertype" class="form-control shadow-sm">
                            <option value="">All Card Types</option>
                            <option value="Pokémon" {{ request('supertype') == 'Pokémon' ? 'selected' : '' }}>Pokémon</option>
                            <option value="Trainer" {{ request('supertype') == 'Trainer' ? 'selected' : '' }}>Trainer</option>
                            <option value="Energy" {{ request('supertype') == 'Energy' ? 'selected' : '' }}>Energy</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <select name="sort_cards" class="form-control shadow-sm">
                            <option value="number_asc" {{ request('sort_cards') == 'number_asc' ? 'selected' : '' }}>Sort Cards: Number</option>
                            <option value="name_asc" {{ request('sort_cards') == 'name_asc' ? 'selected' : '' }}>Sort Cards: A-Z</option>
                            <option value="name_desc" {{ request('sort_cards') == 'name_desc' ? 'selected' : '' }}>Sort Cards: Z-A</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex">
                        <button class="btn btn-primary shadow-sm flex-grow-1" type="submit">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        @if(request('search') || request('supertype') || request('sort_cards'))
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary shadow-sm ml-2" title="Clear Filters">
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