@extends('admin.layout')

@section('title', 'Edit Tournament: ' . $tournament->name)

@section('content')
<div class="responsive-container">
    
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <h2 class="mb-1 font-weight-bold">Edit Tournament</h2>
            <p class="text-muted mb-0">Update the details for {{ $tournament->name }}.</p>
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

                    {{-- POST to the update route --}}
                    <form action="{{ route('admin.tournaments.update', $tournament->id) }}" method="POST">
                        @csrf
                        {{-- Your web.php uses Route::post for update, so no @method('PUT') is needed unless you change your routes --}}
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">Tournament Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Gym Battle - April 2026" value="{{ old('name', $tournament->name) }}" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Start Date & Time <span class="text-danger">*</span></label>
                                {{-- The 'Y-m-d\TH:i' format is STRICTLY required for HTML5 datetime-local inputs to read the date --}}
                                <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', $tournament->start_date ? $tournament->start_date->format('Y-m-d\TH:i') : '') }}" required>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="font-weight-bold text-dark">Capacity (Players) <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" id="capacityInput" class="form-control" value="{{ old('capacity', $tournament->capacity) }}" min="4" max="16" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">Total Rounds <span class="text-danger">*</span></label>
                            <input type="number" name="total_rounds" id="roundsInput" class="form-control" value="{{ old('total_rounds', $tournament->total_rounds) }}" min="1" max="10" required>
                            <small class="form-text text-muted" id="roundsHelper">Standard Swiss recommendation for 16 players is 4 rounds.</small>
                        </div>

                        <hr class="mt-4 mb-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.tournaments.detail', $tournament->id) }}" class="btn btn-light border font-weight-bold text-muted">Cancel</a>
                            <button type="submit" class="btn btn-primary font-weight-bold px-4">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Warning</h5>
                    <p class="text-muted small mb-2">Changing the <strong>Capacity</strong> below the current number of registered players will not automatically drop existing players.</p>
                    <p class="text-muted small mb-0">Changing the <strong>Total Rounds</strong> while a tournament is already <code>active</code> may cause bracket generation errors.</p>
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

        // Set to TRUE by default on the edit page so we don't accidentally overwrite 
        // the admin's manually saved rounds when the page first loads!
        let userEditedRounds = true;

        roundsInput.addEventListener('input', function() {
            userEditedRounds = true;
        });

        function updateRecommendedRounds() {
            let capacity = parseInt(capacityInput.value) || 0;
            
            if (capacity > 0) {
                let recommendedRounds = Math.ceil(Math.log2(capacity));
                if (recommendedRounds < 1) recommendedRounds = 1;

                roundsInput.placeholder = recommendedRounds;
                roundsHelper.innerHTML = `Standard Swiss recommendation for <strong>${capacity}</strong> players is <strong>${recommendedRounds}</strong> rounds.`;
                
                if (!userEditedRounds) {
                    roundsInput.value = recommendedRounds;
                }
            } else {
                roundsHelper.innerHTML = 'Enter a valid capacity to see the recommended rounds.';
            }
        }

        capacityInput.addEventListener('input', function() {
            // If they change capacity, we allow the script to suggest new rounds again
            userEditedRounds = false; 
            updateRecommendedRounds();
        });
        
        updateRecommendedRounds();
    });
</script>
@endpush