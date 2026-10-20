<?php

namespace App\Support;

use App\Models\SystemSetting;

class TicketNotificationPreferences
{
    public const EVENT_KEYS = [
        'new_ticket' => 'notify_on_new_ticket',
        'assigned' => 'notify_on_assignment',
        'comment' => 'notify_on_comment',
        'resolved' => 'notify_on_status_change',
        'forwarded' => 'notify_on_status_change',
        'reopened' => 'notify_on_status_change',
        'info_requested' => 'notify_on_status_change',
        'status_change' => 'notify_on_status_change',
        'closed' => 'notify_on_close',
    ];

    public static function emailKey(int $userId): string
    {
        return 'user_ticket_email_'.$userId;
    }

    public static function eventEnabled(string $event): bool
    {
        $key = self::EVENT_KEYS[$event] ?? null;
        return $key === null || filter_var(SystemSetting::get($key, true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function mailChannels(string $event, object $notifiable): array
    {
        return self::eventEnabled($event)
            && (!method_exists($notifiable, 'receivesTicketEmails') || $notifiable->receivesTicketEmails())
            ? ['mail'] : [];
    }
}
