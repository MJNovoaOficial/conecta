<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Sin esto el usuario nace inactivo y no puede iniciar sesión, que
            // es una forma confusa de que fallen las pruebas.
            'role' => 'user',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Alguien de la mesa de ayuda: ve y atiende los tickets de todos. */
    public function soporte(): static
    {
        return $this->state(fn () => ['role' => 'support']);
    }

    /** Administrador: además configura la plataforma. */
    public function administrador(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    /** Cuenta desactivada, que no debería poder entrar. */
    public function desactivado(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
