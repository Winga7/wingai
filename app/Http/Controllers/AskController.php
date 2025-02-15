<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Conversation;
use App\Events\ChatMessageStreamed;

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

      // Lancer le stream immédiatement
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

      // Générer et mettre à jour le titre après la réponse complète
      if ($conversation->messages()->count() === 2) {
        $title = (new ChatService())->generateTitle([
          [
            'role' => 'system',
            'content' => "Génère un titre court et naturel pour cette conversation, sans préfixes comme 'Règles importantes' ou 'Comment fonctionne'. Utilise 3 à 6 mots maximum."
          ],
          [
            'role' => 'user',
            'content' => $request->message
          ]
        ]);
        $conversation->update(['title' => $title]);

        // Diffuser le titre une fois qu'il est généré
        broadcast(new ChatMessageStreamed(
          channel: "chat.{$conversation->id}",
          content: '',
          isComplete: true,
          title: $title
        ));
      }

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
