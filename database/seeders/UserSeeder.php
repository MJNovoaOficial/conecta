<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Conecta2024!@');

        // Admin
        User::firstOrCreate(
            ['email' => 'v.herrera@dimak.cl'],
            [
                'name'          => 'Valentina Herrera',
                'password'      => $password,
                'department_id' => 1,
                'role'          => 'admin',
                'is_active'     => true,
            ]
        );

        // Técnicos de soporte
        User::firstOrCreate(
            ['email' => 's.morales@dimak.cl'],
            [
                'name'          => 'Sebastián Morales',
                'password'      => $password,
                'department_id' => 1,
                'role'          => 'support',
                'is_active'     => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'c.reyes@dimak.cl'],
            [
                'name'          => 'Camila Reyes',
                'password'      => $password,
                'department_id' => 1,
                'role'          => 'support',
                'is_active'     => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'm.fuentes@dimak.cl'],
            [
                'name'          => 'Matías Fuentes',
                'password'      => $password,
                'department_id' => 2,
                'role'          => 'support',
                'is_active'     => true,
            ]
        );

        // Usuarios finales
        $usuarios = [
            ['name' => 'Isabel Torres',    'email' => 'i.torres@dimak.cl',    'dept' => 2],
            ['name' => 'Felipe Contreras', 'email' => 'f.contreras@dimak.cl', 'dept' => 3],
            ['name' => 'Daniela Pizarro',  'email' => 'd.pizarro@dimak.cl',   'dept' => 2],
            ['name' => 'Rodrigo Castillo', 'email' => 'r.castillo@dimak.cl',  'dept' => 4],
            ['name' => 'Javiera Muñoz',    'email' => 'j.munoz@dimak.cl',     'dept' => 3],
            ['name' => 'Nicolás Vega',     'email' => 'n.vega@dimak.cl',      'dept' => 4],
            ['name' => 'Andrea Silva',     'email' => 'a.silva@dimak.cl',     'dept' => 2],
            ['name' => 'Pablo Guzmán',     'email' => 'p.guzman@dimak.cl',    'dept' => 5],
        ];

        foreach ($usuarios as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'          => $u['name'],
                    'password'      => $password,
                    'department_id' => $u['dept'],
                    'role'          => 'user',
                    'is_active'     => true,
                ]
            );
        }
    }
}
