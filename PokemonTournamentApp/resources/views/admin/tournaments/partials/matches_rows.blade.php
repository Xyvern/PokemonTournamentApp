@forelse($matches as $match)
    @php
        $p1Name = $match->player1->user->nickname ?? 'Unknown';
        $p2Name = $match->player2 ? ($match->player2->user->nickname ?? 'Unknown') : 'Bye';

        $isPlayer1 = Auth::check() && Auth::id() === $match->player1->user->id;
        $isPlayer2 = Auth::check() && $match->player2 && Auth::id() === $match->player2->user->id;
        $isMyMatch = $isPlayer1 || $isPlayer2;

        $isAdminView = $isAdmin ?? false;

        // THE FIX: Check if this match is in the currently active, ongoing round
        $isEditable = false;
        if ($isAdminView) {
            $currentActiveRound = $match->tournament->matches->max('round_number');
            $isEditable = ($match->tournament->status === 'active' && $match->round_number === $currentActiveRound);
        }
    @endphp

    <tr class="{{ (!$isAdminView && $isMyMatch) ? 'table-warning' : '' }}">
        
        @if($isAdminView)
            <td class="align-middle font-weight-bold text-muted">{{ $loop->iteration }}</td>
        @endif

        <td class="align-middle {{ $match->result_code === 1 ? 'font-weight-bold text-success' : '' }}">
            {{ $p1Name }} <span class="badge badge-pill badge-light border ml-1">{{ $match->player1->points }}pts</span>
        </td>

        <td class="align-middle {{ $match->result_code === 2 ? 'font-weight-bold text-success' : '' }}">
            @if($match->player2)
                {{ $p2Name }} <span class="badge badge-pill badge-light border ml-1">{{ $match->player2->points }}pts</span>
            @else
                <span class="text-muted font-italic">Bye</span>
            @endif
        </td>

        <td class="align-middle">
            @if($match->result_code !== null)
                @if($match->result_code === 1) 3 - 0
                @elseif($match->result_code === 2) 0 - 3
                @elseif($match->result_code === 3) 1 - 1
                @endif
            @else
                <span class="badge badge-warning">In Progress</span>
            @endif
        </td>

        {{-- Action Column(s) --}}
        @if($isAdminView)
            <td class="align-middle">
                {{-- THE FIX: Only show the Edit button if it's the current active round --}}
                @if($isEditable)
                    <button type="button" 
                        class="btn btn-sm btn-outline-info" 
                        onclick="openEditMatchModal({{ $match->id }}, '{{ addslashes($p1Name) }}', '{{ addslashes($p2Name) }}')">
                        Edit Result
                    </button>
                @else
                    <span class="text-muted small"><i class="fas fa-lock mr-1"></i> Locked</span>
                @endif
            </td>
            <td class="align-middle">
                @if($match->result_code !== null)
                    <span class="badge badge-success">Completed</span>
                @else
                    <a href="/play?match_id={{ $match->id }}&user_id={{ Auth::id() }}" class="btn btn-sm btn-secondary" style="width: 50%">Watch</a>
                @endif
            </td>
        @endif
    </tr>
@empty
    <tr>
        <td colspan="{{ ($isAdmin ?? false) ? 6 : 4 }}" class="text-center py-4 text-muted">
            No matches found for this round.
        </td>
    </tr>
@endforelse