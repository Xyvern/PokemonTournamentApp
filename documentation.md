# Pokémon Tournament Application Documentation

Welcome to the documentation for the Pokémon Tournament Web Application. This document provides an overview of the system architecture, database models, key features, and user journeys.

---

## 1. System Overview

The Pokémon Tournament Web Application is a platform where players can sync Pokémon TCG cards, build and save custom decks, participate in organized tournaments (e.g., Swiss brackets), chat in real-time, and spectate or play games via an integrated Unity WebGL game.

### Tech Stack
- **Framework**: Laravel 11 (PHP 8.2+)
- **Frontend Template**: AdminLTE 3 / Bootstrap 4 / jQuery
- **CSS Styling**: Custom styling overlays on top of Bootstrap 4 / AdminLTE
- **Real-Time Integration**: Firebase Firestore (for Live Match Chat)
- **Payment Gateway**: Midtrans Snap Integration (for upgrading to Premium status)
- **Game client**: Unity WebGL embedding

---

## 2. Architecture & Directory Structure

```
PokemonTournamentApp/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # Handlers for login, registration, logout
│   │   │   ├── AdminController.php         # Admin dashboard/actions (bracket updates, etc.)
│   │   │   ├── AdminSiteController.php     # Admin page rendering
│   │   │   ├── GameDataController.php      # Unity game integration API endpoints
│   │   │   ├── PlayerController.php        # Player actions (deck saving, tournament join)
│   │   │   ├── PlayerSiteController.php    # Player page rendering
│   │   │   └── SiteController.php          # Public views (cards list, general profiles)
│   │   └── Middleware/
│   │       ├── BlockAdmin.php              # Stops admins from entering the player portal
│   │       └── IsAdmin.php                 # Restricts routes to admin users only
│   └── Models/
│       ├── User.php                        # Players & Admin users (ELO, role, matches)
│       ├── Card.php                        # Pokemon card details
│       ├── GlobalDeck.php                  # Unique 60-card lists (with SHA-256 hash)
│       ├── Deck.php                        # Player-owned instance of a GlobalDeck
│       ├── DeckContent.php                 # Pivot model linking GlobalDecks to Cards (quantities)
│       ├── Archetype.php                   # Deck archetypes (e.g., "Charizard ex")
│       ├── Tournament.php                  # Tournament settings, status, rounds
│       ├── TournamentEntry.php             # Players registered in a tournament with their chosen deck
│       └── TournamentMatch.php             # Match pairings, round numbers, scores
├── database/
│   ├── migrations/                         # DB Schemas definitions
│   └── seeders/                            # Pre-populated cards, archetypes, users, and tournaments
├── resources/
│   └── views/
│       ├── admin/                          # Admin portal templates
│       ├── player/                         # Player portal templates
│       ├── game/                           # Unity WebGL page (play.blade.php)
│       └── partials/                       # Global reusable layouts and toasts
└── routes/
    ├── web.php                             # Web routes
    └── api.php                             # API endpoints for unity client communication
```

---

## 3. Database Schema & Key Models

### Users & Authentication
- **User**: Represents any user (Player or Admin).
  - `role`: `1` for Player, `2` for Admin.
  - `elo`: Rating system for tournament standings.
  - `matches_played` / `matches_won`: Performance records.
  - `upgrade_status`: Links to payment tier (Premium features like removing ads).

### Cards & Decks
- **Card**: Preloaded from the Pokémon TCG API, storing supertypes (Pokémon, Trainer, Energy), subtypes, rarity, images, and rule texts.
- **GlobalDeck**: Represents a unique, canonical deck list.
  - `deck_hash`: SHA-256 hash generated alphabetically by sorting card API IDs and quantity (`api_id:qty|`).
- **Deck**: A user-customized deck that references a `GlobalDeck`.
- **DeckContent**: Pivot table mapping a `GlobalDeck` to individual `Card` records, specifying the quantity of each card.

### Tournaments & Matchmaking
- **Tournament**: Handles organized play with statuses: `registration`, `active`, and `completed`.
- **TournamentEntry**: Represents a user registering for a specific tournament with a specific deck. Records their final rank, total wins, losses, and ties.
- **TournamentMatch**: Matches within a round. Pairs up entries and tracks the result (who won, who lost).

---

## 4. Key Features & Workflows

### Deck Prefilling & Cloning
Users can view a deck in detail (`show.blade.php`). By clicking the **Copy Deck** button:
1. The deck's global SHA-256 hash is copied to the clipboard.
2. The user is redirected to the Deck Creation portal (`create.blade.php?copy=HASH`).
3. The server retrieves the cards from the `GlobalDeck` schema and pre-populates the builder list.

### Spectator Mode & Live Chat
Matches are played in the Unity WebGL client:
- Active matches can be spectated by players, admins, and logged-out visitors (guests).
- Guests are assigned the username `"Guest"` and id `"guest"` automatically, enabling them to spectate and participate in the real-time Firebase live chat alongside logged-in users.
