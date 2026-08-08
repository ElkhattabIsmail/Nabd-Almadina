<?php

namespace Database\Factories;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'citoyen',
            'departement_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'agent',
            'departement_id' => Departement::factory(),
        ]);
    }

    public function citoyen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'citoyen',
            'departement_id' => null,
        ]);
    }
}
