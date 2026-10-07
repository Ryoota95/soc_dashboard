<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/api/dashboard', [DashboardController::class, 'stats']);
Route::get('/api/vulnerabilities', [DashboardController::class, 'vulnerabilities']);
Route::get('/agents/{name}', [AgentController::class, 'show']);
Route::get('/api/agents/{name}', [AgentController::class, 'data']);