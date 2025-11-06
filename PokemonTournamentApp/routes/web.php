<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login',[AuthController::class, 'login'])->name('login');
Route::get('/register',[AuthController::class, 'register'])->name('register');

Route::post('/login', [AuthController::class, 'login'])->name('dologin');
Route::post('/register', [AuthController::class, 'register'])->name('doregister');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');