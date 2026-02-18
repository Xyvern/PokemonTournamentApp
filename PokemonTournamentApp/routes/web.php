<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSiteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerSiteController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login',[AuthController::class, 'login'])->name('login');
Route::get('/register',[AuthController::class, 'register'])->name('register');

Route::post('/login', [AuthController::class, 'doLogin'])->name('dologin');
Route::post('/register', [AuthController::class, 'doRegister'])->name('doregister');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('sets')->name('sets.')->group(function () {
    Route::get('/', [PlayerSiteController::class, 'playerSets'])->name('index');
    Route::get('/{id}', [PlayerSiteController::class, 'playerSetDetail'])->name('detail');
});

Route::prefix('tournaments')->name('tournaments.')->group(function () {
    Route::get('/', [SiteController::class, 'tournaments'])->name('index');
    Route::get('/{id}', [SiteController::class, 'tournamentDetail'])->name('detail');
    Route::post('/{id}/register', [PlayerController::class, 'registerTournament'])->name('register');
    Route::post('/{id}/drop', [PlayerController::class, 'dropTournament'])->name('drop');
    Route::get('{id}/matches', [PlayerController::class, 'fetchRoundMatches'])->name('matches.fetch');
});

Route::prefix('cards')->name('cards.')->group(function () {
    // fix batch to prevent overloading, filter ga jalan
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

    // check lagi decknya jalan ato ga, filter ga jalan, jangan overload kartu
    Route::get('/my-decks', [PlayerSiteController::class, 'myDecks'])->name('mydecks');
    Route::get('/decks/create', [PlayerSiteController::class, 'createDeck'])->name('createDeck');
    Route::post('/decks/store', [PlayerController::class, 'storeDeck'])->name('storeDeck');
});

Route::get('/decks/{deck}', [SiteController::class, 'showDeck'])->name('showDeck');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminSiteController::class, 'adminDashboard'])->name('dashboard');
    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'adminTournaments'])->name('index');
        Route::get('/create', [AdminSiteController::class, 'createTournament'])->name('create');
        Route::post('/store', [AdminController::class, 'storeTournament'])->name('store');
        Route::get('/{id}/edit', [AdminSiteController::class, 'editTournament'])->name('edit');
        Route::post('/{id}/update', [AdminController::class, 'updateTournament'])->name('update');
        Route::post('/{id}/delete', [AdminController::class, 'deleteTournament'])->name('delete');
    });
});

// create archetype after tournament
// pikiran laporan nya admin
// middleware