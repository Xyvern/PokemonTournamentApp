@extends('player.layout')

@section('title', 'Cards Collection')

@section('content')
    {{-- CSS for Hover Animation --}}
    <style>
        .card-hover {
            transition: transform 0.25s ease;
            cursor: pointer;
            border-radius: 8px;
        }
        .card-hover:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.25);
            z-index: 10;
        }
    </style>

    {{-- MANDATORY WRAPPER --}}
    <div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw; display:flex; flex-direction: column; align-items: center;">
        
        <h1 style="margin-top: 2vh; margin-bottom: 2vh;">Cards Collection</h1>

        {{-- Controls --}}
        <div class="w-100 mb-4">
            <div class="d-flex gap-2">
                <input id="searchInput" type="text" class="form-control" placeholder="Search cards by name...">
                
                {{-- Values here are simple 'pokemon', 'trainer', etc. --}}
                <select id="typeFilter" class="form-control" style="max-width: 200px;">
                    <option value="">All Types</option>
                    <option value="pokemon">Pokémon</option>
                    <option value="trainer">Trainer</option>
                    <option value="energy">Energy</option>
                </select>
            </div>
        </div>

        {{-- Card Grid --}}
        <div class="w-100">
            <div class="row" id="cardList">
                @foreach($cards as $card)
                    {{-- Small Grid Layout --}}
                    <div class="col-xl-1 col-lg-2 col-md-2 col-sm-3 col-4 mb-3 px-2 card-entry"
                         data-name="{{ strtolower($card->name) }}"
                         data-type="{{ $card->supertype }}" 
                         {{-- We keep the raw supertype (e.g. "Pokémon") and normalize it in JS --}}
                    >
                        
                        <a href="{{ route('cards.detail', ['id' => $card->api_id]) }}">
                            <img 
                                src="{{ $card->images->small }}" 
                                alt="{{ $card->name }}" 
                                class="img-fluid card-hover"
                                style="width: 100%;"
                            >
                        </a>

                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination Container --}}
        <div id="pagination" class="w-100 mb-5" style="display: flex; justify-content: center;"></div>

    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // =============================================
            // ============ INIT & HELPERS =================
            // =============================================
            
            const allCards = Array.from(document.querySelectorAll(".card-entry"));
            const searchInput = document.getElementById("searchInput");
            const typeFilter = document.getElementById("typeFilter");
            const paginationContainer = document.getElementById("pagination");

            let currentPage = 1;
            const pageSize = 48; // Kept at 48 for the smaller grid
            let filteredCards = allCards;

            // Normalize helper (Fixes the Pokémon accent issue)
            function normalize(str) {
                if (!str) return "";
                return str
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "") // Removes accents (é -> e)
                    .toLowerCase()
                    .replace(/\s+/g, "");
            }

            // =============================================
            // ============ FILTER LOGIC ===================
            // =============================================

            function filterCards() {
                let search = searchInput.value.toLowerCase();
                // Normalize the filter value (e.g. user selects "pokemon", stays "pokemon")
                let type = normalize(typeFilter.value);

                filteredCards = allCards.filter(card => {
                    let name = card.dataset.name;
                    // Normalize the card data (e.g. "Pokémon" becomes "pokemon")
                    let cardType = normalize(card.dataset.type);

                    let matchesSearch = name.includes(search);
                    // Compare normalized values
                    let matchesType = !type || cardType === type;

                    return matchesSearch && matchesType;
                });

                currentPage = 1;
                renderPage();
            }

            // =============================================
            // ============ PAGINATION LOGIC ===============
            // =============================================

            function renderPage() {
                let start = (currentPage - 1) * pageSize;
                let end = start + pageSize;
                let paginatedItems = filteredCards.slice(start, end);

                // Hide all cards
                allCards.forEach(c => (c.style.display = "none"));

                // Show only current page
                paginatedItems.forEach(c => (c.style.display = "block"));

                renderPaginationControls();
            }

            function renderPaginationControls() {
                let totalPages = Math.ceil(filteredCards.length / pageSize);
                
                if (filteredCards.length === 0) {
                    paginationContainer.innerHTML = '<p class="text-center text-muted">No cards found.</p>';
                    return;
                }

                if (totalPages <= 1) {
                    paginationContainer.innerHTML = '';
                    return;
                }

                paginationContainer.innerHTML = `
                    <div class="d-flex align-items-center mt-3 gap-3" style="width: 30%; justify-content: space-evenly;">
                        <button class="btn btn-primary btn-sm" id="btnPrev" ${currentPage === 1 ? 'disabled' : ''}>
                            Previous
                        </button>
                        
                        <span class="font-weight-bold small">Page ${currentPage} of ${totalPages}</span>

                        <button class="btn btn-primary btn-sm" id="btnNext" ${currentPage === totalPages ? 'disabled' : ''}>
                            Next
                        </button>
                    </div>
                `;

                // Bind events dynamically
                document.getElementById('btnPrev')?.addEventListener('click', prevPage);
                document.getElementById('btnNext')?.addEventListener('click', nextPage);
            }

            function nextPage() {
                let totalPages = Math.ceil(filteredCards.length / pageSize);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPage();
                    window.scrollTo(0, 0);
                }
            }

            function prevPage() {
                if (currentPage > 1) {
                    currentPage--;
                    renderPage();
                    window.scrollTo(0, 0);
                }
            }

            // =============================================
            // ============ EVENTS =========================
            // =============================================

            searchInput.addEventListener("input", filterCards);
            typeFilter.addEventListener("change", filterCards);

            // Initial load
            renderPage();
        });
    </script>
@endsection