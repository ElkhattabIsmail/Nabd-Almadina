<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalementTest extends TestCase
{
    use RefreshDatabase;

    public function test_citoyen_can_create_signalement_and_ai_analyzes_it(): void
    {
        $citoyen = User::factory()->citoyen()->create();

        $response = $this->actingAs($citoyen, 'sanctum')
            ->postJson('/api/signalements', [
                'description' => 'Il y a un grand trou dangereux sur la chaussée de ma rue',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Signalement créé et analysé par l\'IA avec succès')
            ->assertJsonPath('data.category', 'Voirie')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('signalements', [
            'user_id' => $citoyen->id,
            'category' => 'Voirie',
        ]);
    }

    public function test_citoyen_can_only_view_their_own_signalement(): void
    {
        $citoyen1 = User::factory()->citoyen()->create();
        $citoyen2 = User::factory()->citoyen()->create();

        $signalement1 = Signalement::factory()->create(['user_id' => $citoyen1->id]);

        // Citoyen 1 can view
        $this->actingAs($citoyen1, 'sanctum')
            ->getJson("/api/signalements/{$signalement1->id}")
            ->assertStatus(200);

        // Citoyen 2 is forbidden
        $this->actingAs($citoyen2, 'sanctum')
            ->getJson("/api/signalements/{$signalement1->id}")
            ->assertStatus(403);
    }

    public function test_only_agent_can_update_signalement_status(): void
    {
        $citoyen = User::factory()->citoyen()->create();
        $agent = User::factory()->agent()->create();
        $signalement = Signalement::factory()->create();

        // Citoyen cannot update status
        $this->actingAs($citoyen, 'sanctum')
            ->patchJson("/api/signalements/{$signalement->id}/statut", ['status' => 'en_cours'])
            ->assertStatus(403);

        // Agent can update status
        $this->actingAs($agent, 'sanctum')
            ->patchJson("/api/signalements/{$signalement->id}/statut", ['status' => 'en_cours'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'en_cours');
    }
}
