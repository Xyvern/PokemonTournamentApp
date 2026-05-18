<?php

use App\Http\Controllers\AdminController;   
use App\Http\Controllers\AdminSiteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerSiteController;
use App\Http\Controllers\SiteController;
use App\Http\Middleware\IsAdmin;
use App\Models\Card;
use App\Models\Set;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| 1. DYNAMIC ROOT REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (Auth::check() && Auth::user()->role == 2) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('player.home');
});

// The Midtrans webhook must remain outside of all auth/csrf middleware
Route::post('/midtrans/callback', [PaymentController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| 2. PUBLIC FRONTEND ROUTES (Blocked for Admins)
|--------------------------------------------------------------------------
*/
Route::middleware(['block.admin'])->group(function () {
    
    Route::get('/decks/{deck}', [SiteController::class, 'showDeck'])->name('showDeck');
    Route::get('/play', function (Request $request) { return view('game.play'); })->name('play');

    Route::prefix('sets')->name('sets.')->group(function () {
        Route::get('/', [PlayerSiteController::class, 'playerSets'])->name('index');
        Route::get('/{id}', [PlayerSiteController::class, 'playerSetDetail'])->name('detail');
    });

    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [SiteController::class, 'tournaments'])->name('index');
        Route::get('/{id}', [SiteController::class, 'tournamentDetail'])->name('detail');
        Route::get('/{id}/matches', [PlayerController::class, 'fetchRoundMatches'])->name('matches.fetch');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/', [SiteController::class, 'cards'])->name('index');
        Route::get('/{id}', [SiteController::class, 'cardDetail'])->name('detail');
    });

    Route::prefix('archetypes')->name('archetypes.')->group(function () {
        Route::get('/', [SiteController::class, 'archetypes'])->name('index');
        Route::get('/{id}', [SiteController::class, 'archetypeDetail'])->name('detail');
    });

    Route::prefix('player')->name('player.')->group(function () {
        Route::get('/home', [PlayerSiteController::class, 'playerHome'])->name('home');
        Route::get('/leaderboard', [PlayerSiteController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/profile/{id}', [PlayerSiteController::class, 'playerProfile'])->name('profile');
    });
});

/*
|--------------------------------------------------------------------------
| 3. GUEST ROUTES (Only for users who are NOT logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class, 'login'])->name('login');
    Route::get('/register',[AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'doLogin'])->name('dologin');
    Route::post('/register', [AuthController::class, 'doRegister'])->name('doregister');
});

/*
|--------------------------------------------------------------------------
| 4. AUTHENTICATED PLAYER ROUTES (Must be logged in, NO ADMINS)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// 4B. Protected Player Routes (Must be logged in, NO ADMINS)
Route::middleware(['auth', 'block.admin'])->group(function () {
    
    // Tournament Actions
    Route::post('/tournaments/{id}/register', [PlayerController::class, 'registerTournament'])->name('tournaments.register');
    Route::post('/tournaments/{id}/drop', [PlayerController::class, 'dropTournament'])->name('tournaments.drop');

    Route::prefix('player')->name('player.')->group(function () {
        Route::get('/upgrade', [PlayerSiteController::class, 'upgrade'])->name('upgrade');
        Route::get('/my-decks', [PlayerSiteController::class, 'myDecks'])->name('mydecks');
        Route::get('/decks/create', [PlayerSiteController::class, 'createDeck'])->name('createDeck');
        Route::post('/decks/store', [PlayerController::class, 'storeDeck'])->name('storeDeck');
        Route::get('/edit-profile', [PlayerSiteController::class, 'editProfile'])->name('editProfile');
        Route::post('/edit-profile', [PlayerController::class, 'updateProfile'])->name('updateProfile');
    });
});


/*
|--------------------------------------------------------------------------
| 4. ADMIN ROUTES (Must be logged in AND be an Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [AdminSiteController::class, 'index'])->name('dashboard');
    Route::get('/unassigned-decks', [AdminSiteController::class, 'unassignedDecks'])->name('unassignedDecks');
    Route::post('/assign-archetype', [AdminController::class, 'assignArchetype'])->name('assignArchetype');
    
    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'tournaments'])->name('index');
        Route::get('/create', [AdminSiteController::class, 'createTournament'])->name('create');
        Route::post('/store', [AdminController::class, 'storeTournament'])->name('store');
        Route::get('/{id}', [AdminSiteController::class, 'tournamentDetail'])->name('detail');
        Route::get('/{id}/edit', [AdminSiteController::class, 'editTournament'])->name('edit');
        Route::post('/{id}/update', [AdminController::class, 'updateTournament'])->name('update');
        Route::post('/{id}/delete', [AdminController::class, 'deleteTournament'])->name('delete');
        Route::post('/{id}/cancel', [AdminController::class, 'cancelTournament'])->name('cancel');
        
        // Action Routes
        Route::post('/{id}/start', [AdminController::class, 'startTournament'])->name('start');
        Route::post('/{id}/next-round', [AdminController::class, 'generateNextRound'])->name('nextRound');
        Route::put('/{id}/update-match', [AdminController::class, 'updateMatchResult'])->name('updateMatch');
        Route::post('/{id}/finalize', [AdminController::class, 'finalizeTournament'])->name('finalize');
        Route::post('/{id}/drop-player', [AdminController::class, 'dropPlayer'])->name('dropPlayer');
        
        // AJAX
        Route::get('/{id}/matches', [AdminController::class, 'fetchRoundMatches'])->name('matches.fetch');
    });

    Route::prefix('archetypes')->name('archetypes.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'archetypes'])->name('index');
        Route::get('/create', [AdminSiteController::class, 'createArchetype'])->name('create');
        Route::get('/{id}', [AdminSiteController::class, 'archetypeDetail'])->name('detail');
        Route::post('/store', [AdminController::class, 'storeArchetype'])->name('store');
        Route::get('/{id}/edit', [AdminSiteController::class, 'editArchetype'])->name('edit');
        Route::post('/{id}/update', [AdminController::class, 'updateArchetype'])->name('update');
        Route::post('/{id}/delete', [AdminController::class, 'deleteArchetype'])->name('delete');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'cardDatabase'])->name('index');
        Route::get('/{id}', [AdminSiteController::class, 'cardDetail'])->name('detail');
        Route::post('/sync', [AdminController::class, 'syncCards'])->name('sync');
        Route::post('/{id}/toggle-playable', [AdminController::class, 'togglePlayable'])->name('togglePlayable');
    });

    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'managePlayers'])->name('index');
        Route::post('/{id}/toggle', [AdminController::class, 'togglePlayerStatus'])->name('toggle');
    });

});