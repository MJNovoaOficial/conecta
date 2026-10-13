<?php

namespace App\Models;

use App\Support\SystemSettingsCatalog;
use App\Support\TicketAttachmentLimits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    /**
     * Obtiene el valor de una configuración con caché.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Guardar existencia y valor, nunca el default particular del consumidor.
        // Namespace nuevo para no confundir entradas primitivas del código anterior.
        $entry = Cache::remember("setting_{$key}:v2", 3600, function () use ($key) {
            $setting = static::where('key', $key)->first();
            return $setting ? ['exists'=>true,'value'=>$setting->value,'type'=>$setting->type] : ['exists'=>false];
        });
        if (!$entry['exists']) return $default;
        return match ($entry['type']) {
            'integer' => (int) $entry['value'],
            'boolean' => filter_var($entry['value'], FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($entry['value'], true),
            default => $entry['value'],
        };
    }

    public static function forgetCached(string $key): void
    {
        Cache::forget("setting_{$key}:v2");
        Cache::forget("setting_{$key}");
        if(in_array($key,TicketAttachmentLimits::SETTINGS_KEYS,true)) Cache::forget(TicketAttachmentLimits::CACHE_KEY);
    }

    /**
     * Guarda o actualiza una configuración e invalida caché.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : (string) $value]);
        static::forgetCached($key);
    }

    /**
     * Devuelve todas las configuraciones de un grupo.
     */
    public static function byGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('group', $group)->orderBy('key')->get();
    }

    /**
     * Catálogo visible incluso si faltan filas. Es solo lectura: los modelos
     * nuevos se guardan únicamente si el administrador envía esos parámetros.
     * Conserva valores/metadatos existentes y no afecta get() ni sus consumidores.
     */
    public static function forSettingsPage(): array
    {
        $stored = static::all()->keyBy('key');
        $defaults = collect(SystemSettingsCatalog::defaults())->keyBy('key');
        $rows = $stored->map(function ($setting) use ($defaults) {
            $copy = clone $setting;
            $definition = $defaults->get($copy->key);
            if ($definition) {
                // Presentación canónica del catálogo; no guarda estas correcciones.
                $copy->group = $definition['group'];
                $copy->type = $definition['type'];
                $copy->label = $copy->label ?: $definition['label'];
                $copy->description = $copy->description ?: $definition['description'];
            }
            return $copy;
        });
        foreach ($defaults as $key => $definition) {
            if (!$stored->has($key)) $rows->put($key, new static($definition));
        }
        $settings = [];
        foreach (SystemSettingsCatalog::GROUPS as $group) {
            $settings[$group] = new \Illuminate\Database\Eloquent\Collection(
                $rows->filter(fn ($row) => $row->group === $group)->sortBy('key')->values()->all()
            );
        }
        return $settings;
    }
}
