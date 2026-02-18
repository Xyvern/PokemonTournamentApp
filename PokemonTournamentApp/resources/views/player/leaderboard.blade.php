@extends('player.layout')

@section('title', 'Live Leaderboard')

@section('content')
<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">

    {{-- 1. PAGE HEADER --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        {{-- Title Section --}}
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">
                                Leaderboard
                            </h2>
                            <p class="text-muted mb-0">Top players ranked by Elo rating</p>
                        </div>

                        {{-- Action/Status Section --}}
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-success px-3 py-2 shadow-sm" style="font-size: 0.9rem;">
                                <span class="spinner-grow spinner-grow-sm mr-1" role="status" aria-hidden="true"></span>
                                Live Updates
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. LEADERBOARD TABLE --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 text-center">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="border-top-0 py-3" style="width: 10%;">Rank</th>
                                    <th scope="col" class="border-top-0 py-3 text-left pl-5" style="width: 30%;">Nickname</th>
                                    <th scope="col" class="border-top-0 py-3" style="width: 20%;">Elo Rating</th>
                                    <th scope="col" class="border-top-0 py-3" style="width: 20%;">Win Rate</th>
                                    <th scope="col" class="border-top-0 py-3" style="width: 20%;">Matches</th>
                                </tr>
                            </thead>
                            {{-- ID target for AJAX --}}
                            <tbody id="leaderboard-body">
                                {{-- Load initial data from partial --}}
                                @include('player.leaderboardrow')
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-right text-muted small py-3">
                    Updates automatically every 5 seconds
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        function fetchLeaderboard() {
            // Add a subtle opacity change to indicate refresh (optional visual cue)
            // document.getElementById('leaderboard-body').style.opacity = '0.7';

            fetch("{{ route('player.leaderboard') }}", {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('leaderboard-body').innerHTML = html;
                // document.getElementById('leaderboard-body').style.opacity = '1';
            })
            .catch(error => console.error('Error updating leaderboard:', error));
        }

        // Refresh every 5000ms (5 seconds)
        setInterval(fetchLeaderboard, 5000);
    });
</script>
@endpush