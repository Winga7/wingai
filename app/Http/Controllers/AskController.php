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

        $personalization = auth()->user()->iaPersonalization;

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'currentConversation' => $currentConversation,
            'messages' => $messages,
            'personalization' => $personalization
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

            // Générer le titre dès le premier message
            if ($conversation->messages()->count() === 1) {
                $chatService = new ChatService();
                $title = $chatService->generateTitle([
                    [
                        'role' => 'user',
                        'content' => $request->message
                    ]
                ]);

                $conversation->update(['title' => $title]);
            }

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

            return redirect()->back()
                ->with('message', $response)
                ->with('conversation', $conversation);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function streamMessage(Conversation $conversation, Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model'   => 'nullable|string',
        ]);

        try {
            // Sauvegarder le message utilisateur
            $conversation->messages()->create([
                'content' => $request->input('message'),
                'role'    => 'user',
                'model'   => $request->model
            ]);

            // Créer le message assistant vide avant de commencer le stream
            $assistantMessage = $conversation->messages()->create([
                'content' => '',
                'role'    => 'assistant',
                'model'   => $request->model
            ]);

            // Récupérer l'historique des messages
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($msg) => [
                    'role'    => $msg->role,
                    'content' => $msg->content,
                ])
                ->toArray();

            // Générer le titre si premier message
            if ($conversation->messages()->count() === 1) {
                $title = (new ChatService())->generateTitle([
                    [
                        'role' => 'user',
                        'content' => $request->message
                    ]
                ]);
                $conversation->update(['title' => $title]);
            }

            // Lancer le stream
            $response = (new ChatService())->streamConversation(
                messages: $messages,
                model: $request->model,
                temperature: 0.7,
                conversation: $conversation
            );

            // Mettre à jour le message assistant avec la réponse complète
            $assistantMessage->update([
                'content' => $response
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Nouvelle fonction séparée pour la génération du titre
    private function generateConversationTitle($message)
    {
        if (!$message) return 'Nouvelle conversation';

        $chatService = new ChatService();
        return $chatService->generateTitle([
            [
                'role' => 'user',
                'content' => $message
            ]
        ]);
    }

    // Nouvelle méthode pour la mise à jour du titre
    public function updateTitle(Request $request, Conversation $conversation)
    {
        $conversation->update(['title' => $request->title]);

        return response()->json(['success' => true]);
    }
}
