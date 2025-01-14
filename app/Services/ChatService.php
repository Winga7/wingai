<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    public function __construct()
    {
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->filter(function ($model) {
                    return str_ends_with($model['id'], ':free');
                })
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                    ];
                })
                ->values()
                ->all()
            ;
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
            logger()->info('Envoi du message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];
            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            logger()->info('Réponse reçue:', ['response' => $response]);

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
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make()
        ;
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

                1. Structure du texte :
                   - Utilise des paragraphes courts (2-3 phrases maximum)
                   - Ajoute TOUJOURS une ligne vide entre chaque paragraphe
                   - Évite absolument les blocs de texte denses

                2. Mise en forme :
                   - Utilise des listes à puces pour énumérer des points
                   - Ajoute des sous-titres pour séparer les sections importantes
                   - Indente les exemples ou citations

                3. Lisibilité :
                   - Commence chaque nouvelle idée sur une nouvelle ligne
                   - Utilise des sauts de ligne pour aérer le texte
                   - Privilégie les phrases courtes et concises

                4. Style conversationnel :
                   - Adopte un ton amical mais professionnel
                   - Pose des questions pour encourager l'interaction
                   - Utilise des émojis avec modération pour agrémenter tes réponses

                IMPORTANT : La lisibilité et l'espacement sont PRIORITAIRES.
                EOT,
        ];
    }

    public function generateTitle($messages)
    {
        $prompt = "En te basant sur cette conversation, génère un titre court et pertinent (max 60 caractères) qui résume le sujet principal. Réponds uniquement avec le titre, sans guillemets ni ponctuation supplémentaire.";

        $response = $this->sendMessage(
            messages: array_merge($messages, [
                ['role' => 'user', 'content' => $prompt]
            ]),
            model: 'gpt-3.5-turbo'
        );

        return trim($response);
    }
}
