<?php

use App\Http\Controllers\GameDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/getMatchData/{matchId}', [GameDataController::class, 'getMatchData'])->name('getMatchData');
Route::post('/storeMatchData', [GameDataController::class, 'storeMatchData'])->name('storeMatchData');