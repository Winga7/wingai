<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'mistralai/mistral-7b-instruct';

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
            try {
                logger()->info('Fetching models from OpenRouter API');
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => config('app.url')
                ])->get($this->baseUrl . '/models');

                if (!$response->successful()) {
                    logger()->error('Error fetching models:', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return [[
                        'id' => self::DEFAULT_MODEL,
                        'name' => 'Mistral: Mistral 7B Instruct (free)'
                    ]];
                }

                $models = $response->json('data', []);
                logger()->debug('Raw models:', ['models' => $models]);

                return collect($models)
                    ->filter(function ($model) {
                        // Vérifie si le modèle a une structure de prix et si les prix sont à 0
                        return isset($model['pricing']) &&
                            isset($model['pricing']['prompt']) &&
                            isset($model['pricing']['completion']) &&
                            (float)$model['pricing']['prompt'] === 0.0 &&
                            (float)$model['pricing']['completion'] === 0.0;
                    })
                    ->map(function ($model) {
                        return [
                            'id' => $model['id'],
                            'name' => $model['name'] . ''
                        ];
                    })
                    ->values()
                    ->all();
            } catch (\Exception $e) {
                logger()->error('Exception in getModels:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return [[
                    'id' => self::DEFAULT_MODEL,
                    'name' => 'Mistral: Mistral 7B Instruct (free)'
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

    // public function generateTitle($messages)
    // {
    //     $prompt = "Crée un titre court (3-4 mots) qui résume UNIQUEMENT la question posée, sans ajouter d'interprétation ni de question supplémentaire.
    //     Exemples:
    //     - Question: 'Quel est la nintendo la plus vendu?' → 'Console Nintendo plus vendue'
    //     - Question: 'Comment fonctionne React?' → 'Fonctionnement React'
    //     - Question: 'Quels sont les meilleurs langages de programmation?' → 'Meilleurs langages programmation'

    //     IMPORTANT:
    //     - Ne JAMAIS ajouter de questions
    //     - Utiliser UNIQUEMENT les mots de la question originale
    //     - Ne pas mettre la réponse dans le titre
    //     - Répondre uniquement avec le titre, sans ponctuation ni article";

    //     $response = $this->sendMessage(
    //         messages: array_merge([
    //             [
    //                 'role' => 'system',
    //                 'content' => $prompt
    //             ]
    //         ], $messages),
    //         model: self::DEFAULT_MODEL
    //     );

    //     return trim($response);
    // }
    public function generateTitle($messages)
    {
        $prompt = "Crée un titre court (3-4 mots) qui résume UNIQUEMENT la question posée, en ignorant toute réponse ou information supplémentaire.

    Exemples :
    - Question : 'Quel est le smartphone le plus vendu ?' → 'Smartphone plus vendu'
    - Question : 'Comment fonctionne Laravel ?' → 'Fonctionnement Laravel'
    - Question : 'Quels sont les meilleurs jeux vidéo ?' → 'Meilleurs jeux vidéo'

    Règles importantes :
    - Ne JAMAIS ajouter de réponse dans le titre.
    - Utiliser uniquement les mots de la question originale.
    - Pas de ponctuation, pas d'articles comme 'le', 'la', 'les'.
    - Réponds uniquement avec le titre généré.";

        // Fusionner le prompt avec les messages existants en mettant le prompt en premier
        $response = $this->sendMessage(
            messages: array_merge([
                [
                    'role' => 'system',
                    'content' => $prompt
                ]
            ], $messages),
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
}
