@extends('player.layout')

@section('title', 'Sets Detail')
<style>
    /* Smooth transition for the card */
    .card-hover {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }

    /* Lift and add stronger shadow on hover */
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }

    /* Subtle background change on hover */
    .card-hover:hover .card-body {
        background-color: #e9ecef !important; /* Slightly darker gray */
        transition: background-color 0.3s;
    }
</style>
@section('content')
    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-0">{{ $set->name }}</h1>
                    <small class="text-muted">Set Details & Collection</small>
                </div>
                <span class="badge bg-primary rounded-pill fs-6 px-3 py-2">
                    {{ $set->total }} Cards
                </span>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-lg-4" id="cardList">
                @foreach($cards as $card)
                    <div class="col card-entry">
                        <div class="card h-100 border-0 shadow-sm card-hover">
                            <div class="card-body p-2 d-flex align-items-center justify-content-center bg-light rounded-3">
                                <img 
                                    src="{{ $card->images->small }}" 
                                    alt="{{ $card->name }}" 
                                    class="img-fluid rounded-2"
                                    loading="lazy"
                                    style="max-height: 250px; object-fit: contain;"
                                >
                            </div>
                            <div class="card-footer bg-white border-0 text-center py-2">
                                <small class="text-truncate d-block fw-bold text-dark">
                                    {{ $card->name }}
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection