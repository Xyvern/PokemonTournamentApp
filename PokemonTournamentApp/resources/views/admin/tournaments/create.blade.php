@extends('admin.layout')

@section('title', 'Create Tournament')

@section('content')
<div style="margin-left: 10vw; margin-top: 2vh; margin-right: 10vw;">
    
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <h2 class="mb-1 font-weight-bold">Create Tournament</h2>
            <p class="text-muted mb-0">Schedule a new Gym Battle or Cup event.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger shadow-sm">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.tournaments.store') }}" method="POST">
                        @csrf
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">Tournament Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Gym Battle - April 2026" value="{{ old('name') }}" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Start Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="font-weight-bold text-dark">Capacity (Players) <span class="text-danger">*</span></label>
                                {{-- Changed to a number input with max="16" --}}
                                <input type="number" name="capacity" id="capacityInput" class="form-control" value="{{ old('capacity', 16) }}" min="4" max="16" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">Total Rounds <span class="text-danger">*</span></label>
                            <input type="number" name="total_rounds" id="roundsInput" class="form-control" value="{{ old('total_rounds', 4) }}" min="1" max="10" required>
                            {{-- This text will update dynamically via JavaScript --}}
                            <small class="form-text text-muted" id="roundsHelper">Standard Swiss recommendation for 16 players is 4 rounds.</small>
                        </div>

                        <hr class="mt-4 mb-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.tournaments.index') }}" class="btn btn-light border font-weight-bold text-muted">Cancel</a>
                            <button type="submit" class="btn btn-primary font-weight-bold px-4">
                                <i class="fas fa-save mr-1"></i> Save & Open Registration
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-info-circle text-primary mr-2"></i>Quick Info</h5>
                    <p class="text-muted small mb-2"><strong>Status:</strong> New tournaments will default to the <code>registration</code> status.</p>
                    <p class="text-muted small mb-0"><strong>Format:</strong> All tournaments will default to the Standard format with Swiss pairings.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const capacityInput = document.getElementById('capacityInput');
        const roundsInput = document.getElementById('roundsInput');
        const roundsHelper = document.getElementById('roundsHelper');

        // Flag to check if the user has manually changed the rounds input
        let userEditedRounds = false;

        roundsInput.addEventListener('input', function() {
            userEditedRounds = true;
        });

        function updateRecommendedRounds() {
            let capacity = parseInt(capacityInput.value) || 0;
            
            if (capacity > 0) {
                // Formula: Ceil(Log2(Players))
                let recommendedRounds = Math.ceil(Math.log2(capacity));
                
                // Ensure it doesn't drop below 1
                if (recommendedRounds < 1) recommendedRounds = 1;

                // Update the placeholder and helper text
                roundsInput.placeholder = recommendedRounds;
                roundsHelper.innerHTML = `Standard Swiss recommendation for <strong>${capacity}</strong> players is <strong>${recommendedRounds}</strong> rounds.`;
                
                // Only auto-update the actual input value if the admin hasn't manually typed a different number
                if (!userEditedRounds) {
                    roundsInput.value = recommendedRounds;
                }
            } else {
                roundsHelper.innerHTML = 'Enter a valid capacity to see the recommended rounds.';
            }
        }

        // Listen for changes on the capacity field
        capacityInput.addEventListener('input', updateRecommendedRounds);
        
        // Run once on load to set the initial state
        updateRecommendedRounds();
    });
</script>
@endpush