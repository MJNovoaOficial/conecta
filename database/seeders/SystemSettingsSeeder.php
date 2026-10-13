<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Support\SystemSettingsCatalog;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SystemSettingsCatalog::defaults();

        // Inicialización puntual: completar claves ausentes, nunca restablecer
        // valores ni metadatos personalizados al volver a ejecutar el seeder.
        $created = 0;
        foreach ($settings as $s) {
            $setting = SystemSetting::firstOrCreate(['key' => $s['key']], $s);
            if ($setting->wasRecentlyCreated) {
                SystemSetting::forgetCached($s['key']);
                $created++;
            }
        }

        $this->command?->info('Parámetros añadidos: '.$created.'. Los existentes se conservaron.');
    }
}
