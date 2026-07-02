<?php

use App\Http\Controllers\GameDataController;
use Illuminate\Support\Facades\Route;

Route::get('/getMatchData/{matchId}', [GameDataController::class, 'getMatchData'])->name('getMatchData');
Route::post('/storeMatchData', [GameDataController::class, 'storeMatchData'])->name('storeMatchData');
Route::post('/photon-webhook', [GameDataController::class, 'photonWebhook'])->name('photonWebhook');