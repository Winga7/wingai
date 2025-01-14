<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $conversations = auth()->user()->conversations()->latest()->get();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'models' => [
                ['id' => 'gpt-4', 'name' => 'GPT-4'],
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo'],
            ],
            'selectedModel' => 'gpt-3.5-turbo'
        ]);
    }

    public function create()
    {
        $conversation = auth()->user()->conversations()->create([
            'title' => 'Nouvelle conversation'
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    public function show(Conversation $conversation)
    {
        return Inertia::render('Chat/Show', [
            'conversation' => $conversation->load('messages'),
            'models' => [
                ['id' => 'gpt-4', 'name' => 'GPT-4'],
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo'],
            ],
            'selectedModel' => 'gpt-3.5-turbo'
        ]);
    }
}
