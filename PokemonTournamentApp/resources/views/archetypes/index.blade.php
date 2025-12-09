@extends('player.layout')

@section('title', 'Archetypes')

@section('content')
    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw; display:flex; flex-direction: column; align-items: center;">
        <h1 style="margin-top: 2vh">Archetypes</h1>
        <div class="table-responsive" style="width: 100%; margin-top: 2vh;">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Times played</th>
                        <th scope="col">Win rates</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($archetypes as $archetype)
                    
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection