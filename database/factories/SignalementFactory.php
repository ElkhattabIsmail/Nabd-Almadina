<?php

namespace Database\Factories;

use App\Models\Departement;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SignalementFactory extends Factory
{
    protected $model = Signalement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => fake()->paragraph(),
            'latitude' => fake()->latitude(33.50, 33.60),
            'longitude' => fake()->longitude(-7.65, -7.55),
            'photo' => null,
            'category' => fake()->randomElement(['Voirie', 'Éclairage public', 'Espaces verts', 'Eau et assainissement', 'Accessibilité']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'urgency' => fake()->numberBetween(1, 5),
            'summary' => fake()->sentence(6),
            'status' => 'nouveau',
            'departement_id' => Departement::factory(),
            'incident_id' => null,
        ];
    }
}
