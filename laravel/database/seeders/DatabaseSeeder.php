<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Incident;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Municipal Departments
        $voirie = Departement::create([
            'name' => 'Voirie',
            'description' => 'Gestion des chaussées, nids-de-poule, trottoirs et travaux de rue.',
        ]);

        $eclairage = Departement::create([
            'name' => 'Éclairage public',
            'description' => 'Maintenance des lampadaires, câblages et éclairages urbains.',
        ]);

        $espacesVerts = Departement::create([
            'name' => 'Espaces verts',
            'description' => 'Entretien des parcs, jardins, élagage et arbres tombés.',
        ]);

        $eau = Departement::create([
            'name' => 'Eau et assainissement',
            'description' => 'Gestion des fuites d\'eau, canalisations et réseaux d\'assainissement.',
        ]);

        $accessibilite = Departement::create([
            'name' => 'Accessibilité',
            'description' => 'Aménagements et obstacles PMR (personnes à mobilité réduite).',
        ]);

        // 2. Create Users (Citizens & Municipal Agents)
        $citoyen1 = User::create([
            'name' => 'Fatima Zahra',
            'email' => 'citoyen@nabd.ma',
            'password' => Hash::make('password'),
            'role' => 'citoyen',
        ]);

        $citoyen2 = User::create([
            'name' => 'Ahmed Alami',
            'email' => 'ahmed@nabd.ma',
            'password' => Hash::make('password'),
            'role' => 'citoyen',
        ]);

        $agentVoirie = User::create([
            'name' => 'Agent Said (Voirie)',
            'email' => 'agent.voirie@nabd.ma',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'departement_id' => $voirie->id,
        ]);

        $agentEclairage = User::create([
            'name' => 'Agent Karim (Éclairage)',
            'email' => 'agent.eclairage@nabd.ma',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'departement_id' => $eclairage->id,
        ]);

        $agentEau = User::create([
            'name' => 'Agent Rachid (Eau)',
            'email' => 'agent.eau@nabd.ma',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'departement_id' => $eau->id,
        ]);

        // 3. Create realistic Signalements
        $sig1 = Signalement::create([
            'user_id' => $citoyen1->id,
            'description' => 'Un grand nid-de-poule très dangereux s\'est formé au milieu de l\'avenue Hassan II près du croisement.',
            'latitude' => 33.57311000,
            'longitude' => -7.58984000,
            'category' => 'Voirie',
            'priority' => 'high',
            'urgency' => 4,
            'summary' => 'Grand nid-de-poule dangereux sur l\'avenue Hassan II',
            'status' => 'nouveau',
            'departement_id' => $voirie->id,
        ]);

        $sig2 = Signalement::create([
            'user_id' => $citoyen2->id,
            'description' => 'Énorme trou dans la chaussée sur l\'avenue Hassan II, risque de casser une roue.',
            'latitude' => 33.57320000, // Very close (approx 10m away)
            'longitude' => -7.58990000,
            'category' => 'Voirie',
            'priority' => 'high',
            'urgency' => 4,
            'summary' => 'Trou sur la chaussée avenue Hassan II',
            'status' => 'nouveau',
            'departement_id' => $voirie->id,
        ]);

        $sig3 = Signalement::create([
            'user_id' => $citoyen1->id,
            'description' => 'Deux lampadaires sont totalement éteints dans la rue des Lilas, la rue est plongée dans le noir complet.',
            'latitude' => 33.58000000,
            'longitude' => -7.60000000,
            'category' => 'Éclairage public',
            'priority' => 'medium',
            'urgency' => 3,
            'summary' => 'Lampadaires en panne rue des Lilas',
            'status' => 'nouveau',
            'departement_id' => $eclairage->id,
        ]);

        $sig4 = Signalement::create([
            'user_id' => $citoyen2->id,
            'description' => 'Une fuite d\'eau importante s\'écoule d\'un tuyau sous le trottoir boulevard Zerktouni.',
            'latitude' => 33.58500000,
            'longitude' => -7.61500000,
            'category' => 'Eau et assainissement',
            'priority' => 'high',
            'urgency' => 5,
            'summary' => 'Fuite d\'eau sous trottoir boulevard Zerktouni',
            'status' => 'en_cours',
            'departement_id' => $eau->id,
        ]);

        // 4. Create sample Incident
        $incident = Incident::create([
            'title' => 'Fuite majeure d\'eau Zerktouni',
            'description' => 'Intervention d\'urgence pour fuite sur canalisation principale Zerktouni.',
            'status' => 'en_cours',
            'priority' => 'high',
            'departement_id' => $eau->id,
        ]);

        $sig4->update(['incident_id' => $incident->id]);
    }
}
