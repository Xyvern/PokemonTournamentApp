@extends('player.layout')

@section('title', 'Sets')

@section('content')
    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
        <div class="container-fluid" style="width: 100%;">
            <h1 class="text-center mb-4">Sets</h1>
            @foreach ($sets->pluck('series')->unique() as $series)
                <div class="mb-5">
                    <h3 class="font-weight-bold text-center mb-4">{{ $series }}</h3>
                    <div class="row">
                        @foreach ($sets->where('series', $series) as $set)
                            <div class="col-md-3 col-sm-6 mb-4">
                                <a href="{{ route('sets.detail', $set->id) }}" style="text-decoration: none; color: inherit;">
                                    <div class="card h-100 shadow-sm">
                                        <div style="height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 1vh;">
                                            <img src="{{ $set->images->logo }}" 
                                                alt="{{ $set->name }}" 
                                                style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        </div>
                                        <div class="card-body text-center">
                                            <h5 class="card-title font-weight-bold">
                                                {{ $set->name }} ({{ $set->total }} cards)
                                            </h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection