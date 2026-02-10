@forelse($matches as $match)
    <tr class="{{ Auth::user()->id === $match->player1->user->id || ($match->player2 && Auth::user()->id === $match->player2->user->id) ? 'table-warning' : '' }}">
        {{-- Player 1 --}}
        <td class="align-middle {{ $match->result_code === 1 ? 'font-weight-bold text-success' : '' }}">
            {{ $match->player1->user->nickname ?? 'Unknown' }}
            <span class="badge badge-pill badge-light border ml-1">{{ $match->player1->points }}pts</span>
        </td>

        
        {{-- Player 2 --}}
        <td class="align-middle {{ $match->result_code === 2 ? 'font-weight-bold text-success' : '' }}">
            @if($match->player2)
            {{ $match->player2->user->nickname ?? 'Unknown' }}
            <span class="badge badge-pill badge-light border ml-1">{{ $match->player2->points }}pts</span>
            @else
            <span class="text-muted font-italic">Bye</span>
            @endif
        </td>

        {{-- Result --}}
        <td class="align-middle">
            @if($match->result_code)
                @if($match->result_code === 1) 1 - 0
                @elseif($match->result_code === 2) 0 - 1
                @elseif($match->result_code === 3) ½ - ½
                @endif
            @else
                <span class="badge badge-warning">In Progress</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="text-center py-4 text-muted">No matches found for this round.</td>
    </tr>
@endforelse