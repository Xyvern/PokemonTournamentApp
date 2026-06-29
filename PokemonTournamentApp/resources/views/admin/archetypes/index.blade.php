@extends('admin.layout')

@section('title', 'Manage Archetypes')

@section('content')

{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

{{-- CSS for the Hover Animation --}}
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        display: flex;
        align-items: center;
        height: 100%;
        padding-left: 8px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .hover-lift {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        position: relative;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    a.text-decoration-none:hover {
        text-decoration: none !important;
        color: inherit !important;
    }
    
    /* Admin specific styles */
    .admin-edit-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 20;
        opacity: 0.5;
        transition: opacity 0.2s;
    }
    .hover-lift:hover .admin-edit-btn {
        opacity: 1;
    }
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. PAGE HEADER & CREATE BUTTON --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">Manage Archetypes</h2>
                            <p class="text-muted mb-0">Create, edit, and assign key cards to meta strategies.</p>
                        </div>
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <button type="button" class="btn btn-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#createArchetypeModal">
                                <i class="fas fa-plus-circle mr-1"></i> Create Archetype
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2. ARCHETYPE GRID --}}
    <div class="row">
        @forelse($archetypes as $archetype)
            @php
                // Safe Image Logic
                $image = 'https://asia.pokemon-card.com/id/card-img/products/Back%20of%20card.png';
                if($archetype->keyCard && $archetype->keyCard->images && $archetype->keyCard->images->small) {
                    $image = $archetype->keyCard->images->small;
                }
                
                // Calculate Win Rate safely
                $winRate = $archetype->times_played > 0 ? round(($archetype->wins / $archetype->times_played) * 100) : 0;
            @endphp

            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                <div class="info-box shadow-sm mb-3 align-items-center hover-lift overflow-hidden h-100 bg-white" style="min-height: 100px;">
                    
                    {{-- Edit Button (Triggers Modal) --}}
                    <button type="button" class="btn btn-sm btn-light border shadow-sm admin-edit-btn" 
                            data-toggle="modal" 
                            data-target="#editArchetypeModal-{{ $archetype->id }}">
                        <i class="fas fa-edit text-primary"></i>
                    </button>

                    {{-- Image Section --}}
                    <a href="{{ route('admin.archetypes.detail', ['id' => $archetype->id]) }}" class="d-flex text-decoration-none text-dark w-100">
                        <div class="bg-light elevation-1 d-flex justify-content-center align-items-center rounded-left overflow-hidden" 
                            style="width: 120px; height: 170px; flex-shrink: 0;">
                            <img src="{{ $image }}" 
                                alt="{{ $archetype->name }}" 
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>

                        {{-- Content Section --}}
                        <div class="info-box-content p-2 pl-3">
                            <span class="info-box-text font-weight-bold text-wrap" style="font-size: 1.1rem; line-height: 1.2;">
                                {{ $archetype->name }}
                            </span>
                            
                            <div class="info-box-number text-muted mt-2" style="font-size: 0.9rem;">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-gamepad mr-2 text-primary" style="width: 20px; text-align: center;"></i> 
                                    <span>{{ $archetype->times_played ?? 0 }} Played</span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-trophy mr-2 text-warning" style="width: 20px; text-align: center;"></i> 
                                    <span>{{ $winRate }}% WR</span>
                                </div>
                                
                                <small class="d-block text-muted text-truncate" title="{{ $archetype->keyCard->api_id ?? 'No Key Card' }}">
                                    <i class="fas fa-id-card mr-1"></i> {{ $archetype->keyCard->api_id ?? 'None Assigned' }}
                                </small>
                            </div>
                        </div>
                    </a>

                </div>

                {{-- EDIT MODAL FOR THIS SPECIFIC ARCHETYPE --}}
                <div class="modal fade" id="editArchetypeModal-{{ $archetype->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content border-0 shadow">
                            <form action="{{ route('admin.archetypes.update', $archetype->id) }}" method="POST">
                                @csrf
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2 text-primary"></i>Edit Archetype</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Archetype Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ $archetype->name }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-weight-bold">Key Card API ID</label>
                                        <select name="api_id" class="form-control select2-cards" style="width: 100%;">
                                            <option value=""></option>
                                            @if(isset($archetype->keyCard))
                                                <option value="{{ $archetype->keyCard->api_id }}" selected>{{ $archetype->keyCard->name }} ({{ $archetype->keyCard->api_id }})</option>
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Search by card name or API ID.</small>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary font-weight-bold">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light text-center py-5 border">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Archetypes Found</h4>
                    <p class="mb-0">Click the button above to create your first meta strategy.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- 3. PAGINATION --}}
    @if(method_exists($archetypes, 'links'))
        <div class="d-flex justify-content-center mt-4 mb-5">
            {{ $archetypes->links() }}
        </div>
    @endif

</div>

{{-- CREATE ARCHETYPE MODAL --}}
<div class="modal fade" id="createArchetypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.archetypes.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Create New Archetype</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Archetype Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Dragapult ex" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Key Card API ID (Optional)</label>
                        <select name="api_id" class="form-control select2-cards" style="width: 100%;">
                            <option value=""></option>
                        </select>
                        <small class="form-text text-muted">This sets the display image. You can search by card name or API ID.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function formatCard(card) {
        if (!card.id) {
            return card.text;
        }
        var imgSrc = 'https://images.pokemontcg.io/' + card.id.replace('-', '/') + '.png';
        var $container = $(
            '<div class="d-flex align-items-center">' +
            '<img src="' + imgSrc + '" style="width: 45px; height: auto; margin-right: 12px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" />' +
            '<span class="font-weight-bold">' + card.text + '</span>' +
            '</div>'
        );
        return $container;
    }

    function formatCardSelection(card) {
        if (!card.id) {
            return card.text;
        }
        var imgSrc = 'https://images.pokemontcg.io/' + card.id.replace('-', '/') + '.png';
        var $container = $(
            '<div class="d-flex align-items-center">' +
            '<img src="' + imgSrc + '" style="width: 20px; height: auto; margin-right: 8px; border-radius: 2px;" />' +
            '<span>' + card.text + '</span>' +
            '</div>'
        );
        return $container;
    }

    $(document).ready(function() {
        $('.select2-cards').each(function() {
            $(this).select2({
                dropdownParent: $(this).closest('.modal'),
                placeholder: 'Search for a card...',
                allowClear: true,
                templateResult: formatCard,
                templateSelection: formatCardSelection,
                ajax: {
                    url: '{{ route('admin.cards.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 3,
            });
        });
    });
</script>
@endpush

@endsection