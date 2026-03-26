@extends('admin.layout')

@section('title', 'Assign Archetypes')

@section('content')
<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    
    {{-- 1. PAGE HEADER --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0 font-weight-bold">Unassigned Decks</h2>
                            <p class="text-muted mb-0">Review tournament entries and assign them to the correct Archetype meta.</p>
                        </div>
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <div class="badge badge-light border px-3 py-2 text-muted shadow-sm">
                                <i class="fas fa-layer-group mr-1"></i> Total Unassigned: {{ $entries->total() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4">{{ session('success') }}</div>
    @endif

    {{-- 2. UNASSIGNED DECKS TABLE --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-5">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="border-collapse: collapse;">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-top-0 pl-4" style="width: 5%;"></th>
                                <th class="border-top-0" style="width: 15%;">Date</th>
                                <th class="border-top-0" style="width: 20%;">Player</th>
                                <th class="border-top-0" style="width: 25%;">Tournament</th>
                                <th class="border-top-0" style="width: 25%;">Assign Archetype</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                {{-- The Clickable Row --}}
                                <tr data-toggle="collapse" data-target="#deckList-{{ $entry->id }}" class="accordion-toggle" style="cursor: pointer;">
                                    <td class="align-middle text-center pl-4">
                                        <i class="fas fa-chevron-down text-muted"></i>
                                    </td>
                                    <td class="align-middle text-muted small font-weight-bold">
                                        {{ $entry->tournament->start_date->format('d M Y') }}
                                    </td>
                                    <td class="align-middle font-weight-bold text-dark">
                                        {{ $entry->user->nickname }}
                                    </td>
                                    <td class="align-middle text-dark">
                                        {{ $entry->tournament->name }}
                                    </td>
                                    
                                    {{-- Action Column (Stop Propagation so clicking the form doesn't collapse the row) --}}
                                    <td class="align-middle" onclick="event.stopPropagation();">
                                        <form action="{{ route('admin.assignArchetype') }}" method="POST" class="d-flex align-items-center m-0">
                                            @csrf
                                            <input type="hidden" name="global_deck_id" value="{{ $entry->deck->global_deck_id }}">
                                            
                                            <div class="flex-grow-1 mr-2">
                                                <select name="archetype_id" class="form-control form-control-sm border-dark" onchange="toggleNewInput(this)" required>
                                                    <option value="" disabled selected>Select...</option>
                                                    <optgroup label="Existing Archetypes">
                                                        @foreach($archetypes as $arch)
                                                            <option value="{{ $arch->id }}">{{ $arch->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Actions">
                                                        <option value="new" class="text-primary font-weight-bold">+ Create New Archetype</option>
                                                    </optgroup>
                                                </select>
                                                {{-- Hidden input for new archetype name --}}
                                                <input type="text" name="new_archetype_name" class="form-control form-control-sm mt-1 border-primary" placeholder="Enter new name..." style="display: none;">
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm btn-dark font-weight-bold shadow-sm">Save</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- The Hidden Expanded Row (Decklist) --}}
                                <tr>
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="collapse bg-light border-bottom" id="deckList-{{ $entry->id }}">
                                            <div class="p-4">
                                                <h6 class="font-weight-bold mb-3 text-dark border-bottom pb-2">
                                                    <i class="fas fa-layer-group mr-2"></i>Deck Contents
                                                </h6>
                                                <div class="row">
                                                    @php
                                                        // Group the contents by supertype (Pokémon, Trainer, Energy)
                                                        $contents = $entry->deck->globalDeck->contents;
                                                        $pokemon = $contents->where('card.supertype', 'Pokémon');
                                                        $trainers = $contents->where('card.supertype', 'Trainer');
                                                        $energies = $contents->where('card.supertype', 'Energy');
                                                    @endphp

                                                    <div class="col-md-4">
                                                        <strong class="d-block mb-2 text-success">Pokémon ({{ $pokemon->sum('quantity') }})</strong>
                                                        <ul class="list-unstyled small">
                                                            @foreach($pokemon as $item)
                                                                <li>{{ $item->quantity }}x {{ $item->card->name }} <span class="text-muted">({{ $item->card->api_id }})</span></li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong class="d-block mb-2 text-info">Trainers ({{ $trainers->sum('quantity') }})</strong>
                                                        <ul class="list-unstyled small">
                                                            @foreach($trainers as $item)
                                                                <li>{{ $item->quantity }}x {{ $item->card->name }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong class="d-block mb-2 text-warning">Energies ({{ $energies->sum('quantity') }})</strong>
                                                        <ul class="list-unstyled small">
                                                            @foreach($energies as $item)
                                                                <li>{{ $item->quantity }}x {{ $item->card->name }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle fa-2x mb-3 text-success d-block"></i>
                                        All decks have been assigned an archetype!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="card-footer bg-white border-top-0 pt-3 pb-3">
                        <div class="d-flex justify-content-center">
                            {{ $entries->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Handles the "Create New Archetype" dropdown selection
    function toggleNewInput(selectElement) {
        const inputField = selectElement.nextElementSibling;
        if (selectElement.value === 'new') {
            inputField.style.display = 'block';
            inputField.required = true;
        } else {
            inputField.style.display = 'none';
            inputField.required = false;
            inputField.value = '';
        }
    }
</script>
@endpush