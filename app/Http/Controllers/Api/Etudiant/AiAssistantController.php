<?php

namespace App\Http\Controllers\Api\Etudiant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantController extends Controller
{
    /**
     * Récupère la clé API depuis la config ou le .env
     */
    protected function getApiKey(): string
    {
        return config('services.ai.api_key', env('AI_API_KEY', ''));
    }

    /**
     * Récupère le modèle à utiliser
     * ⚠️ Mis à jour : llama-3.3-70b-versatile a été déprécié par Groq le 16/08/2026
     */
    protected function getModel(): string
    {
        return config('services.ai.model', env('AI_MODEL', 'openai/gpt-oss-120b'));
    }

    /**
     * Prompt système de l'assistant
     */
    protected function getSystemPrompt(): string
    {
        return "Tu es un assistant pédagogique pour la plateforme 'Ny Tiona Digital' (Madagascar). "
            . "Tu aides les étudiants à comprendre leurs cours, résoudre des problèmes et progresser. "
            . "Réponds en français, de manière claire et bienveillante. "
            . "Utilise des exemples concrets quand c'est utile.";
    }

    /**
     * Endpoint principal du chat IA
     */
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|min:1|max:20',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:4000',
        ]);

        $apiKey = $this->getApiKey();
        $model = $this->getModel();

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant IA non configuré (clé API manquante).',
            ], 503);
        }

        try {
            $messages = array_merge(
                [['role' => 'system', 'content' => $this->getSystemPrompt()]],
                $request->messages
            );

            $res = Http::withToken($apiKey)
                ->withOptions([
                    // Utilise le cacert.pem configuré dans php.ini (curl.cainfo)
                    // Le chemin est déjà défini au niveau PHP, pas besoin de le forcer ici.
                    'verify' => true,
                    'timeout' => 60,
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

            if (!$res->successful()) {
                $errorMessage = $res->json('error.message')
                    ?? $res->body()
                    ?? 'Erreur inconnue de Groq';

                Log::error('[AI] Erreur Groq', [
                    'status' => $res->status(),
                    'model' => $model,
                    'body' => $res->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur IA : ' . $errorMessage,
                ], $res->status());
            }

            $reply = $res->json('choices.0.message.content') ?? '';

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $reply,
                    'model' => $model,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[AI] Exception : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur IA : ' . $e->getMessage(),
            ], 500);
        }
    }
}