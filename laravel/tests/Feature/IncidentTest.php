<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Incident;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_validate_regrouping_into_new_incident(): void
    {
        $agent = User::factory()->agent()->create();

        $sig1 = Signalement::factory()->create([
            'description' => 'Nid-de-poule avenue Hassan II',
            'latitude' => 33.5731,
            'longitude' => -7.5898,
        ]);

        $sig2 = Signalement::factory()->create([
            'description' => 'Trou dangereux avenue Hassan II',
            'latitude' => 33.5732,
            'longitude' => -7.5899,
        ]);

        $response = $this->actingAs($agent, 'sanctum')
            ->postJson('/api/incidents/regrouper', [
                'signalement_ids' => [$sig1->id, $sig2->id],
                'title' => 'Incidents regroupés chaussée Hassan II',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Incidents regroupés chaussée Hassan II');

        $this->assertDatabaseHas('signalements', [
            'id' => $sig1->id,
            'status' => 'en_cours',
        ]);
    }

    public function test_cannot_delete_incident_with_attached_signalements(): void
    {
        $agent = User::factory()->agent()->create();
        $incident = Incident::factory()->create();
        $sig = Signalement::factory()->create(['incident_id' => $incident->id]);

        $response = $this->actingAs($agent, 'sanctum')
            ->deleteJson("/api/incidents/{$incident->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Intégrité référentielle respectée: Impossible de supprimer un incident contenant des signalements rattachés.');
    }
}
