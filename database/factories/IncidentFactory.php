<?php

namespace Database\Factories;

use App\Models\Departement;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['ouvert', 'en_cours', 'resolu', 'ferme']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'departement_id' => Departement::factory(),
        ];
    }
}
