<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            CatalogoSeeder::class,
            SystemSettingsSeeder::class,
            UserSeeder::class,
            PriorityRuleSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
