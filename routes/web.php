<?php

use App\Http\Controllers\AskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'ask'])->name('ask.post');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/chat', function () {
        return Inertia::render('Chat/Index', [
            'models' => [
                ['id' => 'gpt-4', 'name' => 'GPT-4'],
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo'],
            ],
            'selectedModel' => 'gpt-3.5-turbo'
        ]);
    })->name('chat');

    Route::post('/chat/ask', [AskController::class, 'handle'])->name('chat.ask');
});
