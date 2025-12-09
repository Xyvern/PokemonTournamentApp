<?php

use App\Http\Controllers\GameDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/getPlayersData', [GameDataController::class, 'getPlayersData']);