<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(
            ['email' => 'admin@dimak.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'department_id' => 1,
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Support Users
        User::firstOrCreate(
            ['email' => 'soporte1@dimak.local'],
            [
                'name' => 'Soporte IT',
                'password' => Hash::make('password'),
                'department_id' => 1,
                'role' => 'support',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'soporte2@dimak.local'],
            [
                'name' => 'Soporte General',
                'password' => Hash::make('password'),
                'department_id' => 2,
                'role' => 'support',
                'is_active' => true,
            ]
        );

        // Regular Users
        User::firstOrCreate(
            ['email' => 'usuario@dimak.local'],
            [
                'name' => 'Usuario Ejemplo',
                'password' => Hash::make('password'),
                'department_id' => 2,
                'role' => 'user',
                'is_active' => true,
            ]
        );
    }
}
