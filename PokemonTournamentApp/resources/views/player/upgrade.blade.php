@extends('player.layout')

@section('title', 'Upgrade to Premium')

@section('content')
<div class="container py-5" style="margin-left: 5vw; margin-right: 5vw; max-width: 90vw;">
    
    <div class="text-center mb-5">
        <h2 class="font-weight-bold display-4">Choose Your Plan</h2>
        <p class="text-muted lead">Upgrade your account to support the platform and unlock exclusive perks.</p>
    </div>

    <div class="row justify-content-center">
        
        {{-- 1. FREE TIER CARD --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm h-100" style="border: 1px solid #e9ecef;">
                <div class="card-header bg-secondary text-white text-center py-4 border-0">
                    <h3 class="font-weight-bold mb-0">STANDARD</h3>
                    <div class="h2 mt-2 font-weight-normal">Rp 0<small style="font-size: 1rem">/Forever</small></div>
                </div>
                <div class="card-body d-flex flex-column">
                    <ul class="list-unstyled flex-grow-1">
                        <li class="mb-3 text-muted">
                            <i class="fas fa-check text-secondary mr-2" style="width: 20px;"></i> Access to all Tournaments
                        </li>
                        <li class="mb-3 text-muted">
                            <i class="fas fa-check text-secondary mr-2" style="width: 20px;"></i> Play Live Matches
                        </li>
                        <li class="mb-3 text-muted">
                            <i class="fas fa-check text-secondary mr-2" style="width: 20px;"></i> Create & Manage Decks
                        </li>
                        <hr>
                        <li class="mb-3 text-muted" style="opacity: 0.6;">
                            <i class="fas fa-ad mr-2" style="width: 20px;"></i> Contains Advertisements
                        </li>
                    </ul>
                    
                    <button class="btn btn-light btn-block btn-lg font-weight-bold mt-4 border" disabled>
                        Your Current Plan
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. PREMIUM TIER CARD --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow h-100" style="border: 2px solid #007bff; transform: scale(1.05); z-index: 10;">
                <div class="card-header bg-primary text-white text-center py-4 border-0">
                    <h3 class="font-weight-bold mb-0"><i class="fas fa-crown text-warning mr-2"></i>PREMIUM</h3>
                    <div class="h2 mt-2 font-weight-bold">Rp 50.000<small style="font-size: 1rem; font-weight: normal;">/30 Days</small></div>
                </div>
                <div class="card-body d-flex flex-column">
                    <ul class="list-unstyled flex-grow-1">
                        <li class="mb-3 text-dark font-weight-bold">
                            <i class="fas fa-check-circle text-primary mr-2" style="width: 20px;"></i> Access to all Tournaments
                        </li>
                        <li class="mb-3 text-dark font-weight-bold">
                            <i class="fas fa-check-circle text-primary mr-2" style="width: 20px;"></i> Play Live Matches
                        </li>
                        <li class="mb-3 text-dark font-weight-bold">
                            <i class="fas fa-check-circle text-primary mr-2" style="width: 20px;"></i> Create & Manage Decks
                        </li>
                        <hr>
                        <li class="mb-3 text-dark font-weight-bold">
                            <i class="fas fa-ban text-danger mr-2" style="width: 20px;"></i> Zero Advertisements
                        </li>
                    </ul>
                    
                    <button id="pay-button" class="btn btn-primary btn-block btn-lg font-weight-bold mt-4 shadow-sm">
                        Upgrade Now
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Midtrans Snap Script --}}
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script type="text/javascript">
    document.getElementById('pay-button').onclick = function () {
        window.snap.pay('{{ $snapToken }}', {
            onSuccess: function(result) {
                // You can redirect to a 'Thank You' page that checks the DB
                window.location.href = "{{ route('player.home') }}";
            },
            onPending: function(result) {
                alert("Waiting for your payment!");
            },
            onError: function(result) {
                alert("Payment failed!");
            }
        });
    };
</script>
@endsection