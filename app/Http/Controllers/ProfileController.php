<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class ProfileController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function show(Request $request)
    {
        logger()->info('ProfileController::show called');

        $models = $this->chatService->getModels();
        logger()->debug('Models in ProfileController:', [
            'models' => $models,
            'user' => $request->user(),
            'preferred_model' => $request->user()->preferred_model
        ]);

        return Inertia::render('Profile/Show', [
            'confirmsTwoFactorAuthentication' => $request->session()->get('confirmsTwoFactorAuthentication'),
            'sessions' => [],  // Pour l'instant, on retourne un tableau vide
            'availableModels' => $models
        ]);
    }

    protected function sessions(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return collect(
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) use ($request) {
            return (object) [
                'agent' => $session->user_agent,
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        });
    }
}
