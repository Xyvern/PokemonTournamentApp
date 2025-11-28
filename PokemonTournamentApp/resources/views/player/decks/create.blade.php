@extends('player.layout')

@section('content')

<style>
.card-list-panel {
    height: 80vh;
    overflow-y: auto;
    border-right: 1px solid #ddd;
}
.deck-panel {
    height: 80vh;
    overflow-y: auto;
}
.card-image {
    width: 100%;
    border-radius: 6px;
    transition: transform .15s ease;
}
.card-image:hover {
    transform: scale(1.05);
    cursor: pointer;
}
.deck-item {
    transition: background .15s ease;
}
.deck-item:hover {
    background: #f1f1f1;
}
</style>

<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="container-fluid">

    <h3 class="mb-3">Create New Deck</h3>

    <div class="row">

        <!-- LEFT: CARD LIST -->
        <div class="col-md-8 card-list-panel">

            <div class="d-flex mb-3">
                <input id="searchInput" type="text" class="form-control mr-2" placeholder="Search cards...">

                <select id="typeFilter" class="form-control" style="max-width: 200px;">
                    <option value="">All Types</option>
                    <option value="pokemon">Pokémon</option>
                    <option value="trainer">Trainer</option>
                    <option value="energy">Energy</option>
                </select>
            </div>

            <div class="row" id="cardList">

                @foreach($cards as $card)
                    <div class="col-md-2 col-sm-3 col-4 mb-3 card-entry"
                        data-name="{{ strtolower($card->name) }}"
                        data-type="{{ strtolower($card->supertype) }}"
                        data-type-normalized="{{ strtolower(\Illuminate\Support\Str::ascii($card->supertype)) }}"
                        data-rarity="{{ strtolower($card->rarity ?? '') }}"
                    >
                        <img 
                            src="{{ $card->images->small }}" 
                            alt="{{ $card->name }}" 
                            class="card-image"
                            onclick="addCard({{ $card->id }}, '{{ addslashes($card->name) }}', '{{ $card->images->small }}', '{{ $card->api_id }}')"
                        >
                    </div>
                @endforeach

            </div>

        </div>

        <!-- RIGHT: DECK LIST -->
        <div class="col-md-4 deck-panel">

            <form action="{{ route('player.storeDeck') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label>Deck Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter deck name" value="Untitled Deck" required>
                </div>

                <h5 class="mt-3">Deck List (<span id="deckCount">0</span>/60)</h5>
                <ul class="list-group mb-3" id="deckList"></ul>

                <input type="hidden" name="cards" id="cardsInput">

                <button type="submit" class="btn btn-success btn-block mb-2">
                    Save Deck
                </button>

                <button type="button" class="btn btn-info btn-block" onclick="checkLegality()">
                    Check Legality
                </button>

            </form>

        </div>

    </div>
</div>

</div>

<script>
// ========== DECK LOGIC (unchanged) ==========
let deck = {}; // cardID => { qty, name, img, rarity }

function isBasicEnergy(name) {
    return /^basic\s.+\senergy$/i.test(name.trim());
}

function addCard(id, name, img, api_id) {
    if (!deck[id]) {
        deck[id] = { qty: 1, name: name, img: img, api_id: api_id };
    } else {
        if (!isBasicEnergy(name) && deck[id].qty >= 4) {
            return alert(`You cannot have more than 4 copies of "${name}".`);
        }
        deck[id].qty++;
    }

    if (!isBasicEnergy(name)) {
        let totalSameName = countCardsByName(name);
        if (totalSameName > 4) {
            deck[id].qty--;
            if (deck[id].qty <= 0) delete deck[id];
            return alert(`You cannot have more than 4 total copies of "${name}".`);
        }
    }

    if (totalCards() > 60) {
        deck[id].qty--;
        if (deck[id].qty <= 0) delete deck[id];
        return alert("Deck cannot exceed 60 cards.");
    }

    renderDeck();
}

function removeCard(id) {
    if (!deck[id]) return;
    deck[id].qty--;
    if (deck[id].qty <= 0) delete deck[id];
    renderDeck();
}

function totalCards() {
    return Object.values(deck).reduce((sum, c) => sum + c.qty, 0);
}

function countCardsByName(name) {
    let total = 0;
    for (let key in deck) {
        if (deck[key].name.toLowerCase() === name.toLowerCase()) {
            total += deck[key].qty;
        }
    }
    return total;
}

function renderDeck() {
    let list = document.getElementById("deckList");
    list.innerHTML = "";

    for (let id in deck) {
        let item = deck[id];

        list.innerHTML += `
            <li class="list-group-item deck-item d-flex justify-content-between align-items-center">
                <div>
                    <img src="${item.img}" style="height:40px; margin-right:8px;">
                    <strong>x${item.qty}</strong> - ${item.name}
                </div>
                <button class="btn btn-sm btn-danger" onclick="removeCard(${id})">-</button>
            </li>
        `;
    }

    document.getElementById("deckCount").innerText = totalCards();
    document.getElementById("cardsInput").value = JSON.stringify(deck);
}

function checkLegality() {

    if (totalCards() !== 60) {
        return alert("❌ Deck must contain exactly 60 cards.");
    }

    let nameCounts = {};
    for (let id in deck) {
        let item = deck[id];
        let name = item.name.toLowerCase();

        if (!nameCounts[name]) nameCounts[name] = 0;
        nameCounts[name] += item.qty;
    }

    for (let name in nameCounts) {
        if (isBasicEnergy(name)) continue;
        if (nameCounts[name] > 4) {
            return alert(`❌ Illegal deck: More than 4 copies of "${name}".`);
        }
    }

    alert("✔ Deck is legal!");
}

// =============================================
// ============ FILTER + PAGINATION ============
// =============================================

// All cards collected from HTML
let allCards = Array.from(document.querySelectorAll(".card-entry"));

let currentPage = 1;
const pageSize = 100;

// Normalize helper
function normalize(str) {
    return str
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .replace(/\s+/g, "");
}

// Apply search + filter + pagination
function filterCards() {
    let search = document.getElementById("searchInput").value.toLowerCase();
    let type = normalize(document.getElementById("typeFilter").value);

    // Filter first
    let visibleCards = allCards.filter(card => {
        let matchesSearch = card.dataset.name.includes(search);

        let cardType = card.dataset.typeNormalized || normalize(card.dataset.type);
        let matchesType = !type || cardType === type;

        return matchesSearch && matchesType;
    });

    // Store filtered results
    filteredCards = visibleCards;

    currentPage = 1;
    renderPage();
}

let filteredCards = allCards;

// =========== PAGINATION LOGIC ===========
function renderPage() {
    let start = (currentPage - 1) * pageSize;
    let end = start + pageSize;
    let paginated = filteredCards.slice(start, end);

    // Hide all cards
    allCards.forEach(c => (c.style.display = "none"));

    // Show only current page
    paginated.forEach(c => (c.style.display = ""));

    renderPaginationControls();
}

function renderPaginationControls() {
    let totalPages = Math.ceil(filteredCards.length / pageSize);
    let pagination = document.getElementById("pagination");

    pagination.innerHTML = `
        <div class="d-flex justify-content-center mt-3 gap-3">
            <button class="btn btn-sm btn-primary" 
                onclick="prevPage()" 
                ${currentPage === 1 ? "disabled" : ""}>
                Prev
            </button>

            <span class="align-self-center">Page ${currentPage} / ${totalPages}</span>

            <button class="btn btn-sm btn-primary" 
                onclick="nextPage()" 
                ${currentPage === totalPages ? "disabled" : ""}>
                Next
            </button>
        </div>
    `;
}

function nextPage() {
    currentPage++;
    renderPage();
}

function prevPage() {
    currentPage--;
    renderPage();
}

// Initial load
renderPage();

document.getElementById("searchInput").addEventListener("input", filterCards);
document.getElementById("typeFilter").addEventListener("change", filterCards);
</script>


@endsection
