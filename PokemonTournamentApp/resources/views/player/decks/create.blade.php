@extends('player.layout')

@section('content')

<style>
/* Layout Containers */
.main-wrapper {
    height: 73vh; 
    display: flex;
    gap: 20px;
}

.card-list-panel, .deck-panel {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.card-list-panel { flex: 2; }
.deck-panel { flex: 1; }

#cardList, #deckBody {
    flex-grow: 1;
    overflow-y: auto;
    padding: 15px;
    align-content: flex-start;
}

.panel-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.panel-footer {
    padding: 10px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.card-image {
    width: 100%;
    border-radius: 6px;
    transition: transform .15s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.card-image:hover {
    transform: scale(1.05);
    cursor: pointer;
}

.deck-item { transition: background .15s ease; border-radius: 4px; margin-bottom: 4px; }
.deck-item:hover { background: #f1f1f1; }
.ace-spec-item { border-left: 5px solid #d63384 !important; background: #fff0f6 !important; }
</style>

<div class="main-wrapper" style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="card-list-panel">
        <div class="panel-header">
            <h4 class="mb-3">Card Database</h4>
            <div class="d-flex">
                <input id="searchInput" type="text" class="form-control mr-2" placeholder="Search cards...">
                <select id="typeFilter" class="form-control" style="max-width: 150px;">
                    <option value="">All Types</option>
                    <option value="pokemon">Pokémon</option>
                    <option value="trainer">Trainer</option>
                    <option value="energy">Energy</option>
                </select>
            </div>
        </div>

        <div class="row m-0" id="cardList">
            @foreach($cards as $card)
                @php
                    $isAceSpec = $card->subtypes->contains('subtype', 'ACE SPEC');
                    $isBasic = $card->subtypes->contains('subtype', 'Basic');
                @endphp
                <div class="col-md-2 col-sm-3 col-4 mb-3 card-entry"
                    data-name="{{ strtolower($card->name) }}"
                    data-type="{{ strtolower(\Illuminate\Support\Str::ascii($card->supertype)) }}"
                >
                    <img 
                        src="{{ $card->images->small }}" 
                        alt="{{ $card->name }}" 
                        class="card-image"
                        onclick="addCard({{ $card->id }}, '{{ addslashes($card->name) }}', '{{ $card->images->small }}', '{{ $card->api_id }}', {{ $isAceSpec ? 'true' : 'false' }}, {{ $isBasic ? 'true' : 'false' }})"
                        loading="lazy"
                    >
                </div>
            @endforeach
        </div>

        <div class="panel-footer" id="pagination-container"></div>
    </div>

    <div class="deck-panel">
        <form action="{{ route('player.storeDeck') }}" method="POST" id="deckForm" style="display: flex; flex-direction: column; height: 100%;">
            @csrf
            <div class="panel-header">
                <div class="form-group mb-0">
                    <label class="small font-weight-bold">DECK NAME</label>
                    <input type="text" name="name" class="form-control" placeholder="Deck Name" value="Untitled Deck" required>
                </div>
                <h5 class="mt-3 mb-0">Your Deck (<span id="deckCount">0</span>/60)</h5>
            </div>

            <div id="deckBody">
                <ul class="list-group list-group-flush" id="deckList">
                    <li class="list-group-item text-muted text-center py-4">Click cards to add them</li>
                </ul>
            </div>

            <div class="panel-footer">
                <input type="hidden" name="cards" id="cardsInput">
                <button type="submit" class="btn btn-success btn-block mb-2 py-2 font-weight-bold">SAVE DECK</button>
                <button type="button" class="btn btn-outline-info btn-block btn-sm" onclick="checkLegality()">CHECK LEGALITY</button>
            </div>
        </form>
    </div>
</div>

<script>
let deck = {}; 
const allCards = Array.from(document.querySelectorAll(".card-entry"));
let filteredCards = [...allCards]; 
let currentPage = 1;
const pageSize = 48; 

function isBasicEnergy(name) {
    return /fire|water|grass|lightning|psychic|fighting|darkness|metal|fairy/i.test(name) && /energy/i.test(name) && !/special/i.test(name);
}

function addCard(id, name, img, api_id, isAceSpec, isBasic) {
    const currentTotal = Object.values(deck).reduce((sum, c) => sum + c.qty, 0);
    if (currentTotal >= 60) return alert("Deck is full (60/60)!");

    // 1. ACE SPEC RULE: Only one per deck
    if (isAceSpec) {
        const hasAceSpec = Object.values(deck).some(c => c.isAceSpec);
        if (hasAceSpec) return alert("❌ Illegal: You can only have ONE ACE SPEC card in your deck.");
    }

    // 2. 4-COPY RULE: (Except Basic Energy)
    if (!isBasicEnergy(name)) {
        const existingQty = Object.values(deck)
            .filter(c => c.name.toLowerCase() === name.toLowerCase())
            .reduce((sum, c) => sum + c.qty, 0);
        
        if (existingQty >= 4) return alert(`❌ Illegal: Max 4 copies of "${name}" allowed.`);
    }

    // Add to state
    if (!deck[id]) {
        deck[id] = { id, name, img, api_id, isAceSpec, isBasic, qty: 1 };
    } else {
        deck[id].qty++;
    }

    renderDeck();
}

function removeCard(id) {
    if (deck[id]) {
        deck[id].qty--;
        if (deck[id].qty <= 0) delete deck[id];
        renderDeck();
    }
}

function renderDeck() {
    const list = document.getElementById("deckList");
    list.innerHTML = "";
    let total = 0;

    const items = Object.entries(deck).sort((a,b) => a[1].name.localeCompare(b[1].name));
    
    if (items.length === 0) {
        list.innerHTML = '<li class="list-group-item text-muted text-center py-4">Click cards to add them</li>';
    }

    items.forEach(([id, item]) => {
        total += item.qty;
        list.innerHTML += `
            <li class="list-group-item deck-item d-flex justify-content-between align-items-center p-2 ${item.isAceSpec ? 'ace-spec-item' : ''}">
                <div class="d-flex align-items-center">
                    <img src="${item.img}" style="height:45px; margin-right:10px;">
                    <div>
                        <strong>x${item.qty}</strong> <br>
                        <small>${item.name}${item.isAceSpec ? ' <span class="badge badge-danger">ACE</span>' : ''}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm text-danger" onclick="removeCard(${id})">&times;</button>
            </li>`;
    });

    document.getElementById("deckCount").innerText = total;
    document.getElementById("cardsInput").value = JSON.stringify(deck);
}

function checkLegality() {
    const deckArray = Object.values(deck);
    const total = deckArray.reduce((sum, c) => sum + c.qty, 0);

    if (total !== 60) {
        return alert(`❌ Illegal: Deck has ${total} cards. Exactly 60 required.`);
    }

    // 1. BASIC POKEMON CHECK: Must have at least 1
    const hasBasic = deckArray.some(c => c.isBasic);
    if (!hasBasic) {
        return alert("❌ Illegal: Deck must contain at least one Basic Pokémon.");
    }

    // 2. ACE SPEC CHECK: Max 1
    const aceCount = deckArray.filter(c => c.isAceSpec).reduce((sum, c) => sum + c.qty, 0);
    if (aceCount > 1) {
        return alert("❌ Illegal: Multiple ACE SPEC cards detected.");
    }

    alert("✔ Deck is legal! At least 1 Basic Pokémon found and 60/60 cards.");
}

function filterCards() {
    const search = document.getElementById("searchInput").value.toLowerCase();
    const type = document.getElementById("typeFilter").value.toLowerCase();
    filteredCards = allCards.filter(card => card.dataset.name.includes(search) && (!type || card.dataset.type === type));
    currentPage = 1;
    renderPage();
}

function renderPage() {
    const start = (currentPage - 1) * pageSize;
    const paginated = filteredCards.slice(start, start + pageSize);
    allCards.forEach(c => c.style.display = "none");
    paginated.forEach(c => c.style.display = "block");
    document.getElementById("cardList").scrollTop = 0;
    renderPaginationControls();
}

function renderPaginationControls() {
    const totalPages = Math.ceil(filteredCards.length / pageSize);
    const container = document.getElementById("pagination-container");
    if (totalPages <= 1) { container.innerHTML = ""; return; }
    container.innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <button type="button" class="btn btn-outline-primary btn-sm mx-2" onclick="changePage(-1)" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>
            <span class="small">Page <strong>${currentPage}</strong> of ${totalPages}</span>
            <button type="button" class="btn btn-outline-primary btn-sm mx-2" onclick="changePage(1)" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
        </div>`;
}

function changePage(step) { currentPage += step; renderPage(); }

document.addEventListener("DOMContentLoaded", () => {
    filterCards();
    document.getElementById("searchInput").addEventListener("input", filterCards);
    document.getElementById("typeFilter").addEventListener("change", filterCards);
});
</script>
@endsection