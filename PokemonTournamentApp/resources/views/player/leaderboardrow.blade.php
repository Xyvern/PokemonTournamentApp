@foreach ($players as $index => $player)
    <tr class="{{ $player->id === Auth::id() ? 'table-primary font-weight-bold shadow-sm' : '' }}">
        <td class="align-middle font-weight-bold" style="font-size: 1.1rem;">
            @if($index + 1 === 1) 
                <span class="badge badge-warning text-dark px-3 py-2 shadow-sm rounded-pill" style="font-size: 1rem;"><i class="fas fa-trophy mr-1 text-white"></i> 1</span>
            @elseif($index + 1 === 2) 
                <span class="badge text-dark px-3 py-2 shadow-sm rounded-pill border" style="font-size: 1rem; background-color: #e2e8f0;"><i class="fas fa-medal mr-1 text-secondary"></i> 2</span>
            @elseif($index + 1 === 3) 
                <span class="badge text-dark px-3 py-2 shadow-sm rounded-pill border" style="font-size: 1rem; background-color: #fbd38d;"><i class="fas fa-award mr-1" style="color: #c05621;"></i> 3</span>
            @else 
                <span class="text-muted">#{{ $index + 1 }}</span>
            @endif
        </td>
        <td class="align-middle text-left pl-4">
            <div class="d-flex align-items-center">
                <div>
                    <a href="{{ route('player.profile', $player->id) }}" class="text-dark text-decoration-none font-weight-bold" style="font-size: 1.05rem;">
                        {{ $player->nickname }}
                    </a>
                    @if($player->id === Auth::id())
                        <span class="badge badge-primary ml-2 rounded-pill shadow-sm">You</span>
                    @endif
                </div>
            </div>
        </td>
        <td class="align-middle">
            <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ $player->elo }}</span>
        </td>
        <td class="align-middle">
            @php
                $winRate = $player->matches_played > 0 ? number_format(($player->matches_won / $player->matches_played) * 100, 1) : 0.0;
                $badgeClass = 'badge-secondary';
                if ($winRate >= 60) $badgeClass = 'badge-success';
                elseif ($winRate >= 50) $badgeClass = 'badge-primary';
                elseif ($winRate > 0) $badgeClass = 'badge-warning';
            @endphp
            <span class="badge {{ $badgeClass }} px-2 py-1 shadow-sm rounded-pill" style="font-size: 0.9rem;">
                {{ $winRate }}%
            </span>
        </td>
        <td class="align-middle text-muted font-weight-bold" style="font-size: 1.05rem;">
            {{ $player->matches_played }}
        </td>
    </tr>
@endforeach