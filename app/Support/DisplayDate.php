<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/** Presentation only: never change the application's UTC clock or a model's dates. */
final class DisplayDate
{
    public static function timezone(): string
    {
        return config('app.display_timezone', 'America/Santiago');
    }

    public static function format(?DateTimeInterface $date, string $format = 'd/m/Y H:i', string $empty = '—'): string
    {
        return $date === null
            ? $empty
            : CarbonImmutable::instance($date)->setTimezone(self::timezone())->format($format);
    }

    /** Input must already have passed date_format:Y-m-d validation. */
    public static function dayStartUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $date, self::timezone())->startOfDay()->utc();
    }

    /** Exclusive upper bound, including days shortened/lengthened by daylight saving. */
    public static function nextDayStartUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $date, self::timezone())->addDay()->startOfDay()->utc();
    }
}
