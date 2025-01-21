<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Conversation;

class AskController extends Controller
{
    public function index(Request $request)
    {
        $models = (new ChatService())->getModels();
        $selectedModel = auth()->user()->preferred_model ?? ChatService::DEFAULT_MODEL;
        $conversations = auth()->user()->conversations()
            ->latest()
            ->with('messages')
            ->get();

        $currentConversation = null;
        $messages = [];

        if ($request->conversation_id) {
            $currentConversation = Conversation::with('messages')->find($request->conversation_id);
            $messages = $currentConversation ? $currentConversation->messages->sortBy('created_at')->values() : [];
        }

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'currentConversation' => $currentConversation,
            'messages' => $messages
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
            'conversation_id' => 'nullable|exists:conversations,id'
        ]);

        try {
            $conversation = $request->conversation_id
                ? Conversation::find($request->conversation_id)
                : auth()->user()->conversations()->create([
                    'title' => 'Nouvelle conversation',
                    'model' => $request->model
                ]);

            $message = $conversation->messages()->create([
                'content' => $request->message,
                'role' => 'user',
                'model' => $request->model
            ]);

            $response = (new ChatService())->sendMessage(
                messages: $conversation->messages()->orderBy('created_at')->get()->map(function ($msg) {
                    return ['role' => $msg->role, 'content' => $msg->content];
                })->toArray(),
                model: $request->model
            );

            $conversation->messages()->create([
                'content' => $response,
                'role' => 'assistant',
                'model' => $request->model
            ]);

            // Mise à jour du titre après la première interaction complète
            if ($conversation->messages()->count() === 2) {
                $chatService = new ChatService();
                $title = $chatService->generateTitle([
                    [
                        'role' => 'user',
                        'content' => $request->message
                    ],
                    [
                        'role' => 'assistant',
                        'content' => $response
                    ]
                ]);

                $conversation->update(['title' => $title]);
            }

            return redirect()->back()
                ->with('message', $response)
                ->with('conversation', $conversation);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
