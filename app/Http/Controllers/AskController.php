<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use App\Services\ImageService; // Ajoutez cette ligne
use App\Traits\ImageProcessingTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\ChatMessageStreamed;
use Illuminate\Support\Facades\Storage;

class AskController extends Controller
{
  use ImageProcessingTrait;

  protected $chatService;
  protected $imageService; // Ajoutez cette ligne

  public function __construct(ChatService $chatService, ImageService $imageService) // Modifiez le constructeur
  {
    $this->chatService = $chatService;
    $this->imageService = $imageService; // Ajoutez cette ligne
  }

  public function index(Request $request, ?Conversation $conversation = null)
  {
    $models = $this->chatService->getModels();
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
      'personalization' => $personalization,
      'auth' => [
        'user' => $request->user()
      ]
    ]);
  }

  public function streamMessage(Request $request, Conversation $conversation)
  {
    $request->validate([
      'message' => 'required|string',
      'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
      'model' => 'nullable|string',
    ]);

    try {
      $imageData = null;
      $imagePath = null;

      if ($request->hasFile('image')) {
        try {
          logger()->info('Traitement de l\'image en cours');
          $result = $this->imageService->optimizeAndStore($request->file('image'));
          $imagePath = $result['path'];
          $imageData = $result['base64'];
          logger()->info('Image traitée avec succès', [
            'path' => $imagePath,
            'hasBase64' => !empty($imageData)
          ]);
        } catch (\Exception $e) {
          logger()->error('Erreur traitement image:', [
            'error' => $e->getMessage()
          ]);
          throw new \Exception("Erreur lors du traitement de l'image: " . $e->getMessage());
        }
      }

      $userMessage = $conversation->messages()->create([
        'content' => $request->input('message'),
        'role' => 'user',
        'model' => $request->model,
        'has_images' => !is_null($imagePath),
        'image_url' => $imageData
      ]);

      $assistantMessage = $conversation->messages()->create([
        'content' => '',
        'role' => 'assistant',
        'model' => $request->model
      ]);

      $messages = $conversation->messages()
        ->where('id', '<=', $userMessage->id)
        ->orderBy('created_at')
        ->get()
        ->map(function ($msg) {
          $messageData = [
            'role' => $msg->role,
            'content' => $msg->content
          ];

          if ($msg->has_images && $msg->image_url) {
            $messageData['image_url'] = $msg->image_url;
          }

          return $messageData;
        })
        ->toArray();

      logger()->info('Messages préparés pour le stream', [
        'count' => count($messages),
        'hasImages' => !empty($imageData)
      ]);

      $response = $this->chatService->streamConversation(
        messages: $messages,
        modelId: $request->model,
        temperature: 0.7,
        conversation: $conversation
      );

      $assistantMessage->update(['content' => $response]);

      broadcast(new ChatMessageStreamed(
        channel: "chat.{$conversation->id}",
        content: $response,
        isComplete: true
      ));

      return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
      logger()->error('Erreur streaming:', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  // Nouvelles méthodes utilitaires adaptées de Cédric
  private function canAcceptNewMessage(Conversation $conversation, string $message): bool
  {
    $messages = $this->prepareMessages($conversation);
    $messages[] = [
      'role' => 'user',
      'content' => $message
    ];

    return !$this->chatService->isConversationFull($messages, $conversation->model);
  }

  private function prepareMessages(Conversation $conversation): array
  {
    return $conversation->messages()
      ->orderBy('created_at')
      ->get()
      ->map(fn($msg) => [
        'role' => $msg->role,
        'content' => $msg->content,
        'images' => $msg->has_images ? [$msg->image_url] : null
      ])
      ->toArray();
  }

  private function handleFirstMessage(Conversation $conversation, string $message): void
  {
    $title = $this->generateConversationTitle($message);
    $conversation->update(['title' => $title]);

    broadcast(new ChatMessageStreamed(
      channel: "chat.{$conversation->id}",
      content: '',
      isComplete: true,
      title: $title
    ));
  }

  private function prepareResponse(Request $request, Conversation $conversation)
  {
    if ($request->wantsJson()) {
      return response()->json(['status' => 'success']);
    }

    return Inertia::render('Ask/Index', [
      'conversation' => $conversation->fresh(),
      'messages' => $conversation->messages()->orderBy('created_at', 'asc')->get(),
      'status' => 'success'
    ]);
  }

  private function handleError(\Exception $e, Request $request, ?string $imagePath)
  {
    if ($imagePath) {
      Storage::delete($imagePath);
    }

    logger()->error('Erreur dans streamMessage:', [
      'error' => $e->getMessage(),
      'trace' => $e->getTraceAsString()
    ]);

    if ($request->wantsJson()) {
      return response()->json(['error' => $e->getMessage()], 500);
    }

    return Inertia::render('Ask/Index', [
      'flash' => ['error' => $e->getMessage()]
    ]);
  }

  // Nouvelle fonction séparée pour la génération du titre
  private function generateConversationTitle($message)
  {
    if (!$message) return 'Nouvelle conversation';

    // Utiliser $this->chatService au lieu de new ChatService()
    return $this->chatService->generateTitle([
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

  public function stream(Request $request, ?Conversation $conversation = null)
  {
    $request->validate([
      'message' => 'required|string',
      'model' => 'required|string',
      'image' => 'nullable|image|max:4096', // 4MB max
    ]);

    // ... reste du code
  }
}
