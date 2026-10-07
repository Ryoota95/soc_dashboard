<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/api/dashboard', [DashboardController::class, 'stats']);
Route::get('/api/vulnerabilities', [DashboardController::class, 'vulnerabilities']);