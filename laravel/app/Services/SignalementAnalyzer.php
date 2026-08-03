<?php

namespace App\Services;

use App\Models\Departement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignalementAnalyzer
{
    /**
     * Analyze a citizen signalement description and return structured AI metadata.
     *
     * @param string $description
     * @return array{
     *     category: string,
     *     priority: string,
     *     urgency: int,
     *     summary: string,
     *     department_id: int|null,
     *     department_name: string
     * }
     */
    public function analyze(string $description): array
    {
        $apiKey = config('services.ai.api_key') ?? env('AI_API_KEY') ?? env('OPENAI_API_KEY');
        $endpoint = config('services.ai.endpoint') ?? env('AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions');

        $aiResult = null;

        if ($apiKey && filter_var($endpoint, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post($endpoint, [
                    'model' => env('AI_MODEL', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant IA spécialisé pour la plateforme municipale Nabd Al-Madina. Tu dois analyser les signalements de problèmes urbains en français et répondre UNIQUEMENT par un objet JSON strict valide sans balise markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Analyse le signalement suivant: \"{$description}\".\n" .
                                "Extrais exactement la structure JSON suivante:\n" .
                                "{\n" .
                                "  \"category\": \"Voirie|Éclairage public|Espaces verts|Eau et assainissement|Accessibilité|Déchets et Propreté|Autre\",\n" .
                                "  \"priority\": \"low|medium|high\",\n" .
                                "  \"urgency\": 1-5,\n" .
                                "  \"summary\": \"Résumé clair et court du problème\",\n" .
                                "  \"department\": \"Voirie|Éclairage public|Espaces verts|Eau et assainissement|Accessibilité\"\n" .
                                "}",
                        ],
                    ],
                    'temperature' => 0.2,
                ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');
                    if ($content) {
                        // Clean markdown code blocks if present
                        $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
                        $decoded = json_decode($cleanJson, true);
                        if (is_array($decoded) && isset($decoded['category'])) {
                            $aiResult = $decoded;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('AI SignalementAnalyzer API call failed: ' . $e->getMessage());
            }
        }

        // Fallback or heuristic classification if AI API is unavailable or failed
        if (!$aiResult) {
            $aiResult = $this->fallbackHeuristic($description);
        }

        // Map department name to DB record
        $departmentName = $aiResult['department'] ?? 'Voirie';
        $department = Departement::where('name', 'LIKE', '%' . $departmentName . '%')->first();

        if (!$department) {
            $department = Departement::firstOrCreate([
                'name' => $departmentName,
            ], [
                'description' => 'Département municipal ' . $departmentName,
            ]);
        }

        return [
            'category' => $aiResult['category'] ?? 'Voirie',
            'priority' => in_array($aiResult['priority'] ?? '', ['low', 'medium', 'high']) ? $aiResult['priority'] : 'medium',
            'urgency' => min(5, max(1, (int) ($aiResult['urgency'] ?? 3))),
            'summary' => $aiResult['summary'] ?? substr($description, 0, 100),
            'department_id' => $department->id,
            'department_name' => $department->name,
        ];
    }

    /**
     * Fallback heuristic classifier when external AI service is unreachable.
     */
    private function fallbackHeuristic(string $description): array
    {
        $desc = mb_strtolower($description);

        if (preg_match('/lampadaire|éclairage|eclairage|lumière|lumic|sombre|ampoule|poteau/u', $desc)) {
            return [
                'category' => 'Éclairage public',
                'priority' => 'medium',
                'urgency' => 3,
                'summary' => 'Problème d\'éclairage public signalé: ' . mb_substr($description, 0, 80),
                'department' => 'Éclairage public',
            ];
        }

        if (preg_match('/eau|fuite|tuyau|inondation|canalisation|égout|egout|robinet/u', $desc)) {
            return [
                'category' => 'Eau et assainissement',
                'priority' => 'high',
                'urgency' => 4,
                'summary' => 'Fuite d\'eau ou problème d\'assainissement: ' . mb_substr($description, 0, 80),
                'department' => 'Eau et assainissement',
            ];
        }

        if (preg_match('/arbre|branche|plante|jardin|parc|gazon|herbe/u', $desc)) {
            return [
                'category' => 'Espaces verts',
                'priority' => 'medium',
                'urgency' => 2,
                'summary' => 'Entretien d\'espaces verts ou problème d\'arbre: ' . mb_substr($description, 0, 80),
                'department' => 'Espaces verts',
            ];
        }

        if (preg_match('/fauteuil|handicap|rampe|trottoir|accessibilité|accessibilite/u', $desc)) {
            return [
                'category' => 'Accessibilité',
                'priority' => 'high',
                'urgency' => 4,
                'summary' => 'Obstacle à l\'accessibilité PMR: ' . mb_substr($description, 0, 80),
                'department' => 'Accessibilité',
            ];
        }

        if (preg_match('/poubelle|déchet|dechet|ordure|saleté|salete|nettoyage/u', $desc)) {
            return [
                'category' => 'Déchets et Propreté',
                'priority' => 'low',
                'urgency' => 2,
                'summary' => 'Accumulation de déchets ou nettoyage nécessaire: ' . mb_substr($description, 0, 80),
                'department' => 'Voirie',
            ];
        }

        // Default: Voirie (nid-de-poule, chaussée, trou, etc.)
        $isDanger = preg_match('/danger|grave|urgence|accident|cassé|trou|nid-de-poule/u', $desc);
        return [
            'category' => 'Voirie',
            'priority' => $isDanger ? 'high' : 'medium',
            'urgency' => $isDanger ? 4 : 3,
            'summary' => 'Anomalie sur la voirie: ' . mb_substr($description, 0, 80),
            'department' => 'Voirie',
        ];
    }
}
