@extends('player.layout')

@section('title', 'All Sets')

@section('content')

{{-- CSS for Hover Animation --}}
<style>
    .hover-lift {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="container-fluid" style="width: 100%;">
        
        {{-- 1. PAGE HEADER (Updated to match your snippet) --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <h2 class="mb-0 font-weight-bold">All Sets</h2>
                                <p class="text-muted mb-0">Browse all card expansions and series</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. SETS GRID --}}
        @foreach ($sets->pluck('series')->unique() as $series)
            
            {{-- Series Divider --}}
            <h4 style="margin-top: 4vh; border-bottom: 2px solid #17a2b8; padding-bottom: 10px; margin-bottom: 20px; font-weight: bold; color: #444;">
                {{ $series }}
            </h4>

            <div class="row">
                @foreach ($sets->where('series', $series) as $set)
                    {{-- Grid Column --}}
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4 d-flex justify-content-center">
                        
                        {{-- Card with Hover Effect --}}
                        <div class="card hover-lift shadow-sm h-100" style="width: 100%; border: none;">
                            <a href="{{ route('sets.detail', $set->id) }}" style="text-decoration: none; color: inherit; height: 100%; display: flex; flex-direction: column;">
                                
                                {{-- Image Container --}}
                                <div style="height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-top: 15px; padding: 10px;">
                                    <img src="{{ $set->images->logo }}" 
                                         alt="{{ $set->name }}" 
                                         style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                </div>

                                {{-- Card Body --}}
                                <div class="card-body text-center d-flex flex-column align-items-center justify-content-center" style="width: 100%;">
                                    <h5 class="card-title font-weight-bold mb-1" style="font-size: 1.1rem;">
                                        {{ $set->name }}
                                    </h5>
                                    <span class="badge badge-light border">{{ $set->total }} cards</span>
                                </div>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>
        @endforeach

    </div>
</div>
@endsection