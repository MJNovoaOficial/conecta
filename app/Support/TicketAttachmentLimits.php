<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class TicketAttachmentLimits
{
    public const MAX_FILES = 5;
    public const MAX_SIZE_MB = 50;
    public const SETTINGS_KEYS = ['attachments_global_enabled','max_attachment_size_mb','max_attachments_per_ticket'];
    public const CACHE_KEY = 'settings:ticket-attachments:v1';

    private static function settings(): array
    {
        // Una sola consulta obtiene la política completa, nunca una mezcla de
        // campos de dos guardados distintos. TTL acotado como respaldo.
        return Cache::remember(self::CACHE_KEY,60,fn()=>SystemSetting::whereIn('key',self::SETTINGS_KEYS)->pluck('value','key')->all());
    }

    public static function globallyEnabled(): bool
    {
        return filter_var(self::settings()['attachments_global_enabled']??false,FILTER_VALIDATE_BOOLEAN);
    }

    public static function forRequest(bool $comment=false): array
    {
        $legacy=['files'=>$comment?3:5,'kilobytes'=>$comment?5120:51200];
        $settings=self::settings();
        if(!filter_var($settings['attachments_global_enabled']??false,FILTER_VALIDATE_BOOLEAN)) return $legacy;
        $files=$settings['max_attachments_per_ticket']??null;
        $size=$settings['max_attachment_size_mb']??null;
        if(is_bool($files)||is_bool($size)||filter_var($files,FILTER_VALIDATE_INT)===false
            ||filter_var($size,FILTER_VALIDATE_INT)===false||$files<1||$files>self::MAX_FILES
            ||$size<1||$size>self::MAX_SIZE_MB) return $legacy;
        return ['files'=>(int)$files,'kilobytes'=>(int)$size*1024];
    }
}
