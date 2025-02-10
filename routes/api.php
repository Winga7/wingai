<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/dashboard-stats', [DashboardController::class, 'getStats']);
