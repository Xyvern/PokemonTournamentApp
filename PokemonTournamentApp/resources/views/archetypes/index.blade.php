@extends('player.layout')

@section('title', 'All Archetypes')

@section('content')

{{-- CSS for the Hover Animation --}}
<style>
    .hover-lift {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    /* Ensure the link text doesn't turn blue on hover */
    a.text-decoration-none:hover {
        text-decoration: none !important;
        color: inherit !important;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. PAGE HEADER & SEARCH --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">Archetypes</h2>
                            <p class="text-muted mb-0">Browse all available deck strategies</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. ARCHETYPE GRID (Using Your Home Card Format) --}}
    <div class="row">
        @forelse($archetypes as $archetype)
            @php
                // Safe Image Logic
                $image = 'https://asia.pokemon-card.com/id/card-img/products/Back%20of%20card.png';
                if($archetype->keyCard && $archetype->keyCard->images && $archetype->keyCard->images->small) {
                    $image = $archetype->keyCard->images->small;
                }
            @endphp

            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <a href="{{ route('archetypes.detail', ['id' => $archetype->id]) }}" class="text-decoration-none text-dark">
                    <div class="info-box shadow-sm mb-3 align-items-center hover-lift overflow-hidden h-100" style="min-height: 100px;">
                        
                        {{-- Image Section --}}
                        <div class="bg-light elevation-1 d-flex justify-content-center align-items-center rounded-left overflow-hidden" 
                             style="width: 120px; height: 170px; flex-shrink: 0;"> {{-- Slightly adjusted size to fit grid better --}}
                            <img src="{{ $image }}" 
                                 alt="{{ $archetype->name }}" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>

                        {{-- Content Section --}}
                        <div class="info-box-content p-2 pl-3">
                            <span class="info-box-text font-weight-bold text-wrap" style="font-size: 1.1rem; line-height: 1.2;">
                                {{ $archetype->name }}
                            </span>
                            
                            <div class="info-box-number text-muted mt-2" style="font-size: 0.9rem;">
                                {{-- Played Count --}}
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-gamepad mr-2 text-primary" style="width: 20px; text-align: center;"></i> 
                                    <span>{{ $archetype->times_played ?? 0 }} Played</span>
                                </div>
                                
                                {{-- Win Rate --}}
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-trophy mr-2 text-warning" style="width: 20px; text-align: center;"></i> 
                                    <span>{{ $archetype->win_rate ?? 0 }}% WR</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light text-center py-5 border">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Archetypes Found</h4>
                    <p class="mb-0">Try adjusting your search query.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- 3. PAGINATION --}}
    @if(method_exists($archetypes, 'links'))
        <div class="d-flex justify-content-center mt-4">
            {{ $archetypes->links() }}
        </div>
    @endif

</div>
@endsection