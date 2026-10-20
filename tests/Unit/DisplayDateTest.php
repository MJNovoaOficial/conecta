<?php

namespace Tests\Unit;

use App\Support\DisplayDate;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DisplayDateTest extends TestCase
{
    public static function localDates(): array
    {
        return [
            'hora reportada' => ['2026-10-20 13:39:00', '20/10/2026 10:39'],
            'horario de invierno' => ['2026-06-20 13:39:00', '20/06/2026 09:39'],
            'cambio de día' => ['2026-10-20 02:59:59', '19/10/2026 23:59'],
            'fecha histórica' => ['2025-12-31 23:59:00', '31/12/2025 20:59'],
        ];
    }

    #[DataProvider('localDates')]
    public function test_convierte_la_hora_sin_mutar_el_original_ni_el_reloj_de_laravel(string $utc, string $expected): void
    {
        $date = Carbon::parse($utc, 'UTC');
        $timestamp = $date->getTimestamp();
        $this->assertSame($expected, DisplayDate::format($date));
        $this->assertSame($utc, $date->format('Y-m-d H:i:s'));
        $this->assertSame($timestamp, $date->getTimestamp());
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('UTC', date_default_timezone_get());
    }

    public function test_acepta_fechas_inmutables_y_ausentes_y_conserva_el_formato_solicitado(): void
    {
        $date = CarbonImmutable::parse('2026-10-20 13:39:00', 'UTC');
        $this->assertSame('20/10 10:39', DisplayDate::format($date, 'd/m H:i'));
        $this->assertSame('—', DisplayDate::format(null));
        $this->assertSame('', DisplayDate::format(null, empty: ''));
        $this->assertSame('13:39', $date->format('H:i'));
    }

    public function test_los_limites_del_dia_son_locales_y_usan_fin_exclusivo(): void
    {
        $this->assertSame('2026-10-20 03:00:00', DisplayDate::dayStartUtc('2026-10-20')->toDateTimeString());
        $this->assertSame('2026-10-21 03:00:00', DisplayDate::nextDayStartUtc('2026-10-20')->toDateTimeString());
        $this->assertSame('2026-06-20 04:00:00', DisplayDate::dayStartUtc('2026-06-20')->toDateTimeString());
        $this->assertSame('2026-06-21 04:00:00', DisplayDate::nextDayStartUtc('2026-06-20')->toDateTimeString());
    }

    public function test_el_dia_del_cambio_de_horario_no_se_fuerza_a_24_horas(): void
    {
        $start = DisplayDate::dayStartUtc('2026-09-06');
        $end = DisplayDate::nextDayStartUtc('2026-09-06');
        $this->assertSame('2026-09-06 04:00:00', $start->toDateTimeString());
        $this->assertSame('2026-09-07 03:00:00', $end->toDateTimeString());
        $this->assertEquals(23, $start->diffInHours($end));
    }
}
