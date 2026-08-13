<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure a default department exists (e.g., "General")
        $department = Department::firstOrCreate(
            ['name' => 'General'],
            ['is_active' => true]
        );

        // Create or update the admin user
        User::updateOrCreate(
            ['email' => 'bastoxd9@gmail.com'],
            [
                'name' => 'Maximiliano',
                'password' => Hash::make('secret123'),
                'department_id' => $department->id,
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
