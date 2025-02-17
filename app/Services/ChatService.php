<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Events\ChatMessageStreamed;
use App\Interfaces\WeatherServiceInterface;
use App\Services\ImageService;

class ChatService
{
  private $baseUrl;
  private $apiKey;
  private $client;
  private $imageService;
  public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

  public function __construct(ImageService $imageService)
  {
    $this->imageService = $imageService;
    $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
    $this->apiKey = config('services.openrouter.api_key');
    $this->client = $this->createOpenAIClient();
  }

  /**
   * @return array<array-key, array{
   *     id: string,
   *     name: string,
   *     context_length: int,
   *     max_completion_tokens: int,
   *     pricing: array{prompt: int, completion: int}
   * }>
   */
  public function getModels(): array
  {
    return cache()->remember('openai.models', now()->addHour(), function () {
      try {
        $response = Http::withHeaders([
          'Authorization' => 'Bearer ' . $this->apiKey,
          'HTTP-Referer' => config('app.url')
        ])->get($this->baseUrl . '/models');

        if (!$response->successful()) {
          throw new \Exception("Erreur API: " . $response->body());
        }

        $models = $response->json('data', []);

        // Filtrer pour avoir les modèles gratuits et ceux qui supportent la vision
        return collect($models)
          ->filter(function ($model) {
            return str_ends_with($model['id'], ':free') ||
              str_contains($model['id'], 'vision');
          })
          ->map(function ($model) {
            return [
              'id' => $model['id'],
              'name' => $model['name'] . (
                str_contains($model['id'], 'vision') ||
                (isset($model['architecture']['modality']) &&
                  $model['architecture']['modality'] === 'text+image->text')
                ? ' 📸'
                : ''
              ),
              'isPaid' => isset($model['pricing']) &&
                ((float)$model['pricing']['prompt'] > 0 ||
                  (float)$model['pricing']['completion'] > 0),
              'supportsImages' => str_contains($model['id'], 'vision') ||
                (isset($model['architecture']['modality']) &&
                  $model['architecture']['modality'] === 'text+image->text'),
              'pricing' => $model['pricing'] ?? null
            ];
          })
          ->values()
          ->all();
      } catch (\Exception $e) {
        logger()->error('Erreur getModels:', [
          'message' => $e->getMessage()
        ]);

        return [[
          'id' => self::DEFAULT_MODEL,
          'name' => 'meta-llama/llama-3.2-11b-vision-instruct:free',
          'isPaid' => false,
          'supportsImages' => false
        ]];
      }
    });
  }

  /**
   * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
   * @param string|null $model
   * @param float $temperature
   *
   * @return string
   */
  public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
  {
    try {
      $formattedMessages = array_map(function ($message) {
        if (!empty($message['images'])) {
          $content = [];
          // Ajouter le texte
          if (!empty($message['content'])) {
            $content[] = [
              'type' => 'text',
              'text' => $message['content']
            ];
          }
          // Traiter chaque image
          foreach ($message['images'] as $image) {
            $processedImage = $this->processImageForApi($image);
            if ($processedImage) {
              $content[] = $processedImage;
            }
          }
          return [
            'role' => $message['role'],
            'content' => $content
          ];
        }
        return $message;
      }, $messages);

      $lastMessage = end($messages);

      // Si c'est une commande slash
      if (str_starts_with($lastMessage['content'], '/')) {
        return $this->handleSlashCommand($lastMessage['content']);
      }

      try {
        // Ajouter le contexte de personnalisation
        $systemMessage = $this->getSystemMessage();
        array_unshift($messages, $systemMessage);

        // Préparer les messages pour l'API
        $formattedMessages = array_map(function ($message) {
          // Si le message contient une image, le formater correctement
          if (isset($message['image_url'])) {
            return [
              'role' => $message['role'],
              'content' => [
                [
                  'type' => 'text',
                  'text' => $message['content']
                ],
                [
                  'type' => 'image_url',
                  'image_url' => [
                    'url' => $message['image_url']
                  ]
                ]
              ]
            ];
          }

          // Sinon retourner le message tel quel
          return $message;
        }, $messages);

        $response = $this->client->chat()->create([
          'model' => $model ?? self::DEFAULT_MODEL,
          'messages' => $formattedMessages,
          'temperature' => $temperature,
        ]);

        return $response->choices[0]->message->content;
      } catch (\Exception $e) {
        logger()->error('Erreur dans sendMessage:', [
          'message' => $e->getMessage(),
          'trace' => $e->getTraceAsString()
        ]);
        throw $e;
      }
    } catch (\Exception $e) {
      logger()->error('Erreur dans sendMessage:', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  public function changeTitle(array $messages, string $model = null, float $temperature = 0.7): string
  {
    // $lastMessage = end($messages);

    // Si c'est une commande slash
    // if (str_starts_with($lastMessage['content'], '/')) {
    //   return $this->handleSlashCommand($lastMessage['content']);
    // }

    try {
      logger()->info('Changement de titre', [
        'model' => $model,
        'temperature' => $temperature,
      ]);

      $models = collect($this->getModels());
      if (!$model || !$models->contains('id', $model)) {
        $model = self::DEFAULT_MODEL;
        logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
      }

      $messages = [$this->getTitleMessagePrompt(), ...$messages];
      $response = $this->client->chat()->create([
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature,
      ]);

      logger()->info('Titre reçue:', ['response' => $response]);

      $content = $response->choices[0]->message->content;

      return $content;
    } catch (\Exception $e) {
      if ($e->getMessage() === 'Undefined array key "choices"') {
        throw new \Exception("Limite de messages atteinte");
      }

      logger()->error('Erreur dans sendMessage:', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      throw $e;
    }
  }

  private function createOpenAIClient(): \OpenAI\Client
  {
    try {
      $client = \OpenAI::factory()
        ->withApiKey($this->apiKey)
        ->withBaseUri($this->baseUrl)
        ->withHttpHeader('HTTP-Referer', config('app.url')) // Important pour OpenRouter
        ->make();

      logger()->info('Client OpenRouter créé', [
        'base_url' => $this->baseUrl,
        'has_api_key' => !empty($this->apiKey),
        'referer' => config('app.url')
      ]);

      return $client;
    } catch (\Exception $e) {
      logger()->error('Erreur création client:', [
        'message' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * @return array{role: 'system', content: string}
   */
  private function getChatSystemPrompt(): array
  {
    $user = auth()->user();
    $now = now()->locale('fr')->format('l d F Y H:i');

    return [
      'role' => 'system',
      'content' => <<<EOT
                Tu es Kon-chan, un assistant de chat amical et organisé. La date et l'heure actuelle est le {$now}.
                Tu es actuellement en conversation avec {$user->name}.

                Directives de formatage obligatoires pour toutes tes réponses :

                1. Structure Markdown :
                  - Utilise des titres avec ## pour les sections principales
                  - Utilise des sous-titres avec ### pour les sous-sections
                   - Mets en **gras** les points importants
                   - Utilise *l'italique* pour l'emphase
                  - Crée des listes avec - ou 1. 2. 3.
                  - Utilise des blocs de code avec ```langage pour le code
                  - Utilise > pour les citations

                2. Mise en page :
                  - Sépare TOUJOURS les paragraphes par une ligne vide
                  - Limite chaque paragraphe à 2-3 phrases
                  - Utilise des listes à puces pour les énumérations
                  - Ajoute des liens [texte](url) si pertinent

                3. Style de réponse :
                  - Structure tes réponses avec des sections claires
                  - Commence par une brève introduction
                  - Développe les points principaux
                  - Termine par une conclusion ou une question

                4. Ton et interaction :
                  - Reste amical et professionnel
                  - Pose des questions pour encourager l'interaction
                  - Utilise des émojis avec modération

                IMPORTANT : La lisibilité et l'espacement sont PRIORITAIRES.
                EOT,
    ];
  }

  private function getTitleMessagePrompt(): array
  {
    return [
      'role' => 'system',
      'content' => <<<EOT
            Tu dois créer un titre court (2-4 mots) qui reflète UNIQUEMENT le contenu du message de l'utilisateur.

            Règles STRICTES :
            - Utilise UNIQUEMENT les mots présents dans le message de l'utilisateur
            - Ne fais AUCUNE interprétation ou analyse SEO
            - Si le message est une salutation (bonjour, salut, etc), utilise "Nouvelle conversation"
            - Pas d'articles (le, la, les, un, une, des)
            - Pas de ponctuation
            - Réponds uniquement avec le titre

            Exemples :
            Message: "Bonjour comment ça va ?"
            Réponse: "Nouvelle conversation"

            Message: "Comment fonctionne Laravel ?"
            Réponse: "Fonctionnement Laravel"

            Message: "Quel est le meilleur smartphone ?"
            Réponse: "Meilleur smartphone"
            EOT,
    ];
  }

  public function generateTitle($messages)
  {
    // Fusionner le prompt avec les messages existants en mettant le prompt en premier
    $response = $this->changeTitle(
      $messages,
      model: self::DEFAULT_MODEL // Utilisation du modèle configuré
    );

    return $this->sanitizeTitle(trim($response));
  }

  // Fonction pour nettoyer et ajuster le titre généré si nécessaire
  private function sanitizeTitle($title)
  {
    // Supprimer la ponctuation et les caractères spéciaux inutiles
    $title = preg_replace('/[^a-zA-Z0-9\sàâäéèêëïîôöùûüç]/u', '', $title);

    // Limiter à 4 mots maximum
    $words = explode(' ', $title);
    if (count($words) > 10) {
      $title = implode(' ', array_slice($words, 0, 4));
    }

    return trim($title);
  }

  private function getDefaultCommands(): array
  {
    return [
      [
        'command' => '/help',
        'description' => 'Affiche la liste des commandes disponibles',
        'prompt' => 'Liste toutes les commandes disponibles avec leur description'
      ],
      [
        'command' => '/meteo',
        'description' => 'Affiche la météo actuelle et les prévisions pour une ville',
        'prompt' => 'weather_command',
        'handler' => 'handleWeatherCommand'
      ],
      [
        'command' => '/resume',
        'description' => 'Résume un texte',
        'prompt' => 'Fais un résumé concis du texte fourni en gardant les points essentiels'
      ],
      [
        'command' => '/translate',
        'description' => 'Traduit un texte',
        'prompt' => 'Traduit le texte fourni en français'
      ],
      [
        'command' => '/humour',
        'description' => 'Fais une blague',
        'prompt' => 'Fais une blague'
      ]
    ];
  }

  private function handleWeatherCommand(string $city): string
  {
    $weatherService = new OpenWeatherMapService();

    try {
      $current = $weatherService->getCurrentWeather($city);
      $forecast = $weatherService->getForecast($city);

      if (!isset($current['main'])) {
        return "Désolé, je ne trouve pas la ville '$city'. Veuillez vérifier l'orthographe.";
      }

      $now = date('H:i');
      $response = "# Météo {$current['name']}\n\n";
      $response .= "{$now} | " . date('l', time()) . "\n\n";

      // Conditions actuelles
      $response .= "## " . ucfirst($current['weather'][0]['description']) . "\n\n";
      $response .= "<div class='current-weather-card'>\n\n";
      $response .= "![Conditions actuelles](https://openweathermap.org/img/wn/{$current['weather'][0]['icon']}@4x.png)\n\n";
      $response .= "# {$current['main']['temp']}°\n\n";
      $response .= "Sensation de {$current['main']['feels_like']}°\n\n";

      // Informations supplémentaires
      $response .= "| | |\n|---|---|\n";
      $response .= "| 💨 Vent | {$current['wind']['speed']} km/h |\n";
      $response .= "| 💧 Humidité | {$current['main']['humidity']}% |\n";
      $response .= "| ☁️ Nuages | {$current['clouds']['all']}% |\n";
      if (isset($current['rain']['1h'])) {
        $response .= "| 🌧️ Pluie (1h) | {$current['rain']['1h']} mm |\n";
      }
      $response .= "</div>\n\n";

      // Prévisions sur 7 jours
      $response .= "## Prévisions sur 7 jours\n\n";
      $response .= "<div class='forecast-grid'>\n\n";

      $dailyForecasts = [];
      foreach ($forecast['list'] as $item) {
        $date = date('Y-m-d', $item['dt']);
        if (!isset($dailyForecasts[$date])) {
          $dailyForecasts[$date] = $item;
        }
      }

      foreach (array_slice($dailyForecasts, 0, 7) as $date => $day) {
        $dayName = date('l', strtotime($date));
        $dayNum = date('d M', strtotime($date));

        $response .= "### {$dayName}\n";
        $response .= "{$dayNum}\n\n";
        $response .= "![{$day['weather'][0]['description']}](https://openweathermap.org/img/wn/{$day['weather'][0]['icon']}@2x.png)\n\n";

        if (isset($day['rain']['3h'])) {
          $response .= "🌧️ {$day['rain']['3h']}mm\n";
        }

        $response .= "**{$day['main']['temp_max']}°** / {$day['main']['temp_min']}°\n";
        $response .= "💨 {$day['wind']['speed']} km/h\n\n";
        $response .= "---\n\n";
      }

      $response .= "</div>\n\n";

      // Lever et coucher du soleil
      if (isset($current['sys']['sunrise']) && isset($current['sys']['sunset'])) {
        $response .= "## Lever et coucher du soleil\n\n";
        $response .= "🌅 " . date('H:i', $current['sys']['sunrise']) . "\n";
        $response .= "🌇 " . date('H:i', $current['sys']['sunset']) . "\n\n";
      }

      return $response;
    } catch (\Exception $e) {
      return "Désolé, une erreur est survenue lors de la récupération des données météo. Veuillez réessayer plus tard.";
    }
  }

  private function handleSlashCommand(string $message): string
  {
    $parts = explode(' ', trim($message));
    $command = $parts[0];
    $args = array_slice($parts, 1);

    // Récupérer la personnalisation de l'utilisateur
    $user = auth()->user();
    $personalization = $user->iaPersonalization;
    $userName = $personalization?->identity ?? $user->name;

    // Fusionner les commandes
    $allCommands = array_merge(
      $this->getDefaultCommands(),
      $personalization?->slash_commands ?? []
    );

    foreach ($allCommands as $cmd) {
      if ($cmd['command'] === $command) {
        if ($command === '/meteo') {
          if (empty($args)) {
            return "Veuillez spécifier une ville. Exemple: /meteo Paris";
          }

          $city = implode(' ', $args);
          return $this->handleWeatherCommand($city); // Retourner directement le résultat
        }

        return $this->sendMessage([
          ['role' => 'system', 'content' => $cmd['prompt']],
          ['role' => 'user', 'content' => implode(' ', $args)]
        ]);
      }
    }

    return "Commande non reconnue. Tapez /help pour voir la liste des commandes disponibles.";
  }

  private function generateHelpMessage(array $commands): string
  {
    $helpText = "## Commandes disponibles\n\n";
    foreach ($commands as $cmd) {
      $helpText .= "- **{$cmd['command']}** : {$cmd['description']}\n";
    }
    return $helpText;
  }

  private function getSystemMessage(): array
  {
    $user = auth()->user();
    $personalization = $user->iaPersonalization;
    $now = now()->format('d-m-Y H:i:s');

    $systemContent = "Tu es Kon-chan, un assistant de chat amical et organisé. ";
    $systemContent .= "La date et l'heure actuelle est le {$now}. ";
    $systemContent .= "Tu es actuellement en conversation avec {$user->name}. ";

    // Ajouter l'identité et le comportement personnalisés
    if ($personalization) {
      if ($personalization->identity) {
        $systemContent .= "\nContexte utilisateur : {$personalization->identity}";
      }
      if ($personalization->behavior) {
        $systemContent .= "\nComportement attendu : {$personalization->behavior}";
      }
    }

    return [
      'role' => 'system',
      'content' => $systemContent
    ];
  }

  public function streamConversation(array $messages, ?string $modelId = null, float $temperature = 0.7, $conversation = null)
  {
    try {
      // Formatage des messages avec les images
      $formattedMessages = array_map(function ($message) {
        if (!empty($message['image_url'])) {
          return [
            'role' => $message['role'],
            'content' => [
              [
                'type' => 'text',
                'text' => $message['content']
              ],
              [
                'type' => 'image_url',
                'image_url' => [
                  'url' => $message['image_url']
                ]
              ]
            ]
          ];
        }
        return [
          'role' => $message['role'],
          'content' => $message['content']
        ];
      }, $messages);

      // Ajout du message système en premier
      array_unshift($formattedMessages, $this->getSystemMessage());

      logger()->info('Messages formatés pour l\'API:', [
        'messages' => $formattedMessages,
        'model' => $modelId ?? self::DEFAULT_MODEL
      ]);

      $response = $this->client->chat()->create([
        'model' => $modelId ?? self::DEFAULT_MODEL,
        'messages' => $formattedMessages,
        'temperature' => $temperature,
        'max_tokens' => 2000
      ]);

      if (!isset($response->choices[0]->message->content)) {
        throw new \Exception("Réponse API invalide");
      }

      return $response->choices[0]->message->content;
    } catch (\Exception $e) {
      logger()->error('Erreur streamConversation détaillée:', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'messages' => $messages
      ]);
      throw $e;
    }
  }

  private function handleWeatherFollowUp(string $city, array $weatherData, string $userName): string
  {
    $systemPrompt = <<<EOT
Tu es un expert mode et météo parlant à {$userName}.
Utilise les données météo suivantes pour fournir des recommandations vestimentaires précises et personnalisées.

Instructions:
1. Base tes recommandations sur la température, les précipitations et le vent actuels
2. Organise les suggestions par moment de la journée si pertinent
3. Utilise des emojis appropriés pour chaque type de vêtement
4. Reste cohérent avec le style de conversation précédent

Données météo: {$weatherData}
EOT;

    return $this->sendMessage([
      [
        'role' => 'system',
        'content' => $systemPrompt
      ],
      [
        'role' => 'user',
        'content' => "Oui, je souhaite des recommandations vestimentaires pour {$city} aujourd'hui"
      ]
    ]);
  }

  private function processModelData($model): array
  {
    $supportsVision = str_contains($model['id'], 'vision') ||
      (isset($model['architecture']['modality']) &&
        $model['architecture']['modality'] === 'text+image->text');

    return [
      'id' => $model['id'],
      'name' => $model['name'] . ($supportsVision ? ' 📸' : ''),
      'isPaid' => isset($model['pricing']) &&
        ((float)$model['pricing']['prompt'] > 0 ||
          (float)$model['pricing']['completion'] > 0),
      'supportsImages' => $supportsVision,
      'pricing' => $model['pricing'] ?? null
    ];
  }

  private function modelSupportsImages(string $modelId): bool
  {
    $models = collect($this->getModels());
    $model = $models->firstWhere('id', $modelId);

    return $model['supportsImages'] ?? false;
  }

  private function processImageForApi($imageData): ?array
  {
    try {
      // Si c'est déjà une URL
      if (filter_var($imageData, FILTER_VALIDATE_URL)) {
        return [
          'type' => 'image_url',
          'image_url' => ['url' => $imageData]
        ];
      }

      // Si c'est déjà un base64
      if (str_starts_with($imageData, 'data:image/')) {
        return [
          'type' => 'image_url',
          'image_url' => ['url' => $imageData]
        ];
      }

      // Si c'est un chemin local
      $fullPath = storage_path('app/public/' . $imageData);
      if (file_exists($fullPath)) {
        // Optimiser et convertir en base64
        $optimizedData = $this->imageService->optimizeImage($fullPath);
        $base64 = base64_encode($optimizedData);

        return [
          'type' => 'image_url',
          'image_url' => [
            'url' => "data:image/jpeg;base64,{$base64}"
          ]
        ];
      }

      return null;
    } catch (\Exception $e) {
      logger()->error('Erreur traitement image:', [
        'error' => $e->getMessage(),
        'image' => $imageData
      ]);
      return null;
    }
  }

  public function isConversationFull(array $messages, string $modelId): bool
  {
    try {
      // Récupérer les limites du modèle
      $models = collect($this->getModels());
      $model = $models->firstWhere('id', $modelId);

      if (!$model) {
        logger()->warning('Modèle non trouvé:', ['model_id' => $modelId]);
        return false;
      }

      // Compter les tokens approximatifs (4 caractères = ~1 token en moyenne)
      $totalTokens = 0;
      foreach ($messages as $message) {
        $content = $message['content'];
        if (is_array($content)) {
          $content = implode(' ', array_map(fn($item) => $item['text'] ?? '', $content));
        }
        $totalTokens += strlen($content) / 4;
      }

      // Ajouter une marge de sécurité de 20%
      $totalTokens *= 1.2;

      // Vérifier par rapport à la limite du modèle
      $contextLength = $model['context_length'] ?? 4096; // Valeur par défaut

      logger()->info('Vérification limite contexte:', [
        'total_tokens' => $totalTokens,
        'context_length' => $contextLength
      ]);

      return $totalTokens >= $contextLength;
    } catch (\Exception $e) {
      logger()->error('Erreur vérification conversation:', [
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }
}
