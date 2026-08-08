<?php

namespace App\Services;

use App\Models\Signalement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\AI;
use Illuminate\Support\Facades\Log;

class SimilarityService
{
    /**
     * Find candidate duplicates for a given target signalement.
     *
     * @param Signalement $target
     * @param float $radiusKm Maximum geographic radius in kilometers (default 2.0km)
     * @return array
     */
    public function findDuplicates(Signalement $target, float $radiusKm = 2.0): array
    {
        // 1. Fetch unresolved candidate signalements (excluding target) and same category
        $candidates = Signalement::with(['user', 'departement'])
            ->where('id', '!=', $target->id)
            ->whereIn('status', ['nouveau', 'en_cours'])
            ->when($target->category, fn($query) => $query->where('category', $target->category))
            ->get();

        $results = [];

        foreach ($candidates as $candidate) {
            // Calculate Haversine distance in meters
            $distanceMeters = $this->haversineDistance(
                $target->latitude,
                $target->longitude,
                $candidate->latitude,
                $candidate->longitude
            );

            $distanceKm = $distanceMeters / 1000;

            // Skip if outside radius
            if ($distanceKm > $radiusKm) {
                continue;
            }

            // Calculate textual / spatial similarity score
            $score = $this->calculateSimilarityScore($target, $candidate, $distanceMeters);

            // Filter out weak matches (score >= 40%)
            if ($score >= 40) {
                $results[] = [
                    'candidate_id' => $candidate->id,
                    'candidate' => [
                        'id' => $candidate->id,
                        'description' => $candidate->description,
                        'summary' => $candidate->summary,
                        'category' => $candidate->category,
                        'priority' => $candidate->priority,
                        'urgency' => $candidate->urgency,
                        'status' => $candidate->status,
                        'latitude' => $candidate->latitude,
                        'longitude' => $candidate->longitude,
                        'created_at' => $candidate->created_at->toIso8601String(),
                        'user_name' => $candidate->user->name ?? 'Anonyme',
                        'incident_id' => $candidate->incident_id,
                    ],
                    'similarity_score' => round($score, 2),
                    'distance_meters' => round($distanceMeters, 1),
                    'same_category' => (mb_strtolower($target->category ?? '') === mb_strtolower($candidate->category ?? '')),
                    'verdict' => $score >= 75 ? 'Fortement recommandé' : ($score >= 55 ? 'Doublon probable' : 'Similarité modérée'),
                    'reason' => "Signalement situé à " . round($distanceMeters) . "m (" . ($candidate->category ?? 'même domaine') . ").",
                ];
            }
        }

        // Sort by similarity score descending
        usort($results, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        $aiResults = $this->validateWithAi($target, $results);
        if (!empty($aiResults)) {
            $results = $aiResults;
        }

        return [
            'target_signalement_id' => $target->id,
            'target_summary' => $target->summary,
            'target_category' => $target->category,
            'total_candidates_evaluated' => $candidates->count(),
            'potential_duplicates_count' => count($results),
            'similar_signalements' => $results,
        ];
    }

    private function validateWithAi(Signalement $target, array $results): array
    {
        if (empty($results) || !class_exists(AI::class) || !config('services.ai.api_key')) {
            return $results;
        }

        $topCandidates = array_slice($results, 0, 5);
        $payload = [
            'target_description' => $target->description,
            'target_summary' => $target->summary,
            'target_category' => $target->category,
            'candidates' => array_map(fn($item) => [
                'id' => $item['candidate_id'],
                'description' => $item['candidate']['description'],
                'summary' => $item['candidate']['summary'],
                'distance_meters' => $item['distance_meters'],
                'local_score' => $item['similarity_score'],
            ], $topCandidates),
        ];

        $prompt = "Tu es une IA qui aide à détecter les doublons de signalements urbains. " .
            "En te basant sur la localisation, la catégorie et la description, renvoie uniquement un JSON valide et sans balises Markdown. " .
            "Pour chaque signalement candidat, indique candidate_id, similarity_score (0 à 100), verdict et reason. " .
            "Ne change pas l'identifiant candidat. Voici le signalement cible :\n" .
            "Description : {$payload['target_description']}\n" .
            "Résumé : {$payload['target_summary']}\n" .
            "Catégorie : {$payload['target_category']}\n\n" .
            "Candidats : \n";

        foreach ($payload['candidates'] as $candidate) {
            $prompt .= "- ID: {$candidate['id']}, distance: {$candidate['distance_meters']}m, score: {$candidate['local_score']}\n" .
                "  Description: {$candidate['description']}\n" .
                "  Résumé: {$candidate['summary']}\n";
        }

        $prompt .= "\nRetourne le résultat sous forme de tableau JSON unique : \n[ {\"candidate_id\": 1, \"similarity_score\": 0, \"verdict\": \"...\", \"reason\": \"...\" } ]";

        try {
            $aiResponse = AI::chat()->create([
                'model' => config('services.ai.model', env('AI_MODEL', 'gpt-4o-mini')),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es une IA spécialisée dans la détection de doublons de signalements urbains. Réponds uniquement par JSON.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
            ]);

            $content = data_get($aiResponse, 'choices.0.message.content');
            if ($content) {
                $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
                $decoded = json_decode($cleanJson, true);
                if (is_array($decoded)) {
                    $aiResults = [];
                    foreach ($decoded as $item) {
                        if (!isset($item['candidate_id'], $item['similarity_score'])) {
                            continue;
                        }
                        $candidateId = (int) $item['candidate_id'];
                        $aiResults[$candidateId] = [
                            'similarity_score' => min(100, max(0, (float) $item['similarity_score'])),
                            'verdict' => $item['verdict'] ?? 'Verdict IA indisponible',
                            'reason' => $item['reason'] ?? 'Suggestion IA',
                        ];
                    }

                    if (!empty($aiResults)) {
                        foreach ($results as &$result) {
                            $candidateId = $result['candidate_id'];
                            if (isset($aiResults[$candidateId])) {
                                $result['similarity_score'] = $aiResults[$candidateId]['similarity_score'];
                                $result['verdict'] = $aiResults[$candidateId]['verdict'];
                                $result['reason'] = $aiResults[$candidateId]['reason'];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI SimilarityService call failed: ' . $e->getMessage());
        }

        usort($results, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);
        return $results;
    }

    /**
     * Calculate similarity score (0 to 100) between two signalements.
     */
    private function calculateSimilarityScore(Signalement $target, Signalement $candidate, float $distanceMeters): float
    {
        // Category weight (30 points)
        $categoryScore = 0;
        if ($target->category && $candidate->category && mb_strtolower($target->category) === mb_strtolower($candidate->category)) {
            $categoryScore = 30;
        }

        // Distance weight (40 points - decays with distance up to 1000m)
        $distanceScore = max(0, 40 * (1 - ($distanceMeters / 1500)));

        // Textual similarity (30 points)
        $textScore = $this->textSimilarity($target->description, $candidate->description) * 30;

        return min(100, $categoryScore + $distanceScore + $textScore);
    }

    /**
     * Calculate basic N-gram / similarity percentage between two strings.
     */
    private function textSimilarity(string $str1, string $str2): float
    {
        $s1 = mb_strtolower(trim($str1));
        $s2 = mb_strtolower(trim($str2));

        if ($s1 === $s2) {
            return 1.0;
        }

        similar_text($s1, $s2, $percent);
        return $percent / 100;
    }

    /**
     * Haversine formula to compute distance in meters between two lat/lng pairs.
     */
    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
