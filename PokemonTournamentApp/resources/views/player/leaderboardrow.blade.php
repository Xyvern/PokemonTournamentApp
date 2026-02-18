@foreach ($players as $index => $player)
    <tr class="{{ $player->id === Auth::id() ? 'table-primary font-weight-bold' : '' }}">
        <td>
            @if($index + 1 === 1) 🥇
            @elseif($index + 1 === 2) 🥈
            @elseif($index + 1 === 3) 🥉
            @else {{ $index + 1 }}
            @endif
        </td>
        <td>
            {{-- Make name clickable to go to profile --}}
            <a href="{{ route('player.profile', $player->id) }}" class="text-dark text-decoration-none">
                {{ $player->nickname }}
            </a>
        </td>
        <td>{{ $player->elo }}</td>
        <td>
            {{ $player->matches_played > 0 ? number_format(($player->matches_won / $player->matches_played) * 100, 1) : '0.0' }}%
        </td>
        <td>{{ $player->matches_played }}</td>
    </tr>
@endforeach