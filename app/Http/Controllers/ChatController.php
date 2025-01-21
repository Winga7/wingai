<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Services\ChatService;

class ChatController extends Controller
{
    public function index()
    {
        $conversations = auth()->user()->conversations()
            ->latest()
            ->with(['messages' => function ($query) {
                $query->latest()->first();
            }])
            ->get();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'models' => (new ChatService())->getModels(),
            'selectedModel' => ChatService::DEFAULT_MODEL
        ]);
    }

    public function store(Request $request)
    {
        $conversation = auth()->user()->conversations()->create([
            'title' => 'Nouvelle conversation',
            'model' => $request->model ?? auth()->user()->preferred_model ?? ChatService::DEFAULT_MODEL
        ]);

        return redirect()->route('ask.index', ['conversation_id' => $conversation->id]);
    }

    public function show(Conversation $conversation)
    {
        $conversation->load('messages');

        return Inertia::render('Chat/Show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages,
            'models' => (new ChatService())->getModels(),
            'selectedModel' => $conversation->model
        ]);
    }

    public function updateTitle(Request $request, Conversation $conversation)
    {
        $conversation->update([
            'title' => $request->title
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return redirect()->back();
    }
}
