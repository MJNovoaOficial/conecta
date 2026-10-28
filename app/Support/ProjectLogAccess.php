<?php

namespace App\Support;

use App\Models\ProjectLogEntry;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class ProjectLogAccess
{
    public static function staff(?User $user): bool
    {
        return $user && $user->is_active && in_array($user->role, ['support', 'admin'], true);
    }

    public static function available(): bool
    {
        if (!filter_var(config('project_log.enabled', false), FILTER_VALIDATE_BOOLEAN)) return false;

        $request = request();
        $key = 'conecta.project_log.available';
        if (!$request->attributes->has($key)) {
            $request->attributes->set($key, Schema::hasTable('project_log_projects')
                && Schema::hasTable('project_log_entries') && Schema::hasTable('project_log_attachments'));
        }

        return $request->attributes->get($key) === true;
    }

    public static function canEdit(User $user, ProjectLogEntry $entry): bool
    {
        return self::staff($user) && ($user->isAdmin() || (int) $entry->author_id === (int) $user->id);
    }
}
