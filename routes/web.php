<?php

use App\Http\Controllers\AskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IaPersonalizationController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'ask'])->name('ask.store');
    Route::post('/ask/{conversation}/stream', [AskController::class, 'streamMessage'])->name('ask.stream');

    // Routes pour le chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/conversations', [ChatController::class, 'store'])->name('conversations.store');
    Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('conversations.destroy');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');

    Route::get('/user/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::get('/ia/personalization', [IaPersonalizationController::class, 'index'])->name('ia.personalization.index');
    Route::post('/ia/personalization', [IaPersonalizationController::class, 'store'])->name('ia.personalization.store');
});
