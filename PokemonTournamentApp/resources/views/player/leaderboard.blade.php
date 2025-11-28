@extends('player.layout')

@section('title', 'Leaderboard')

@section('content')
    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw; display:flex; flex-direction: column; align-items: center;">
        <h1 style="margin-top: 2vh">Leaderboard</h1>
        <div class="table-responsive" style="width: 100%; margin-top: 2vh;">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Rank</th>
                        <th scope="col">Nickname</th>
                        <th scope="col">Elo</th>
                        <th scope="col">Winrate</th>
                        <th scope="col">Matches Played</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($players as $index => $player)
                    @if ($player->username == Auth::user()->username)
                        <tr class="table-primary">
                    @else
                        <tr>
                    @endif
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $player->nickname }}</td>
                            <td>{{ $player->elo }}</td>
                            <td>
                                {{ $player->matches_played > 0 ? number_format($player->matches_won / $player->matches_played * 100, 2) : '0' }}%
                            </td>
                            <td>{{ $player->matches_played }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection